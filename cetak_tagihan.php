<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$db || $id <= 0) {
    die("Data Tagihan tidak valid atau database belum terhubung.");
}

$stmt = $db->prepare("
    SELECT t.*, 
           s.dasar_pengenaan, s.tanggal_lapor,
           wp.nama AS nama_wp, wp.npwp, wp.nik, wp.alamat AS alamat_wp,
           jp.nama_pajak, jp.kode_pajak,
           mp.tahun_pajak, mp.bulan_pajak, mp.keterangan AS ket_masa
    FROM tagihan t
    JOIN spt s ON t.id_spt = s.id_spt
    JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
    JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
    JOIN masa_pajak mp ON s.id_masa_pajak = mp.id_masa_pajak
    WHERE t.id_tagihan = ?
");
$stmt->execute([$id]);
$tagihan = $stmt->fetch();

if (!$tagihan) {
    die("Tagihan #$id tidak ditemukan.");
}

// Histori Pembayaran
$stmt_pembayaran = $db->prepare("SELECT * FROM pembayaran WHERE id_tagihan = ? ORDER BY id_pembayaran ASC");
$stmt_pembayaran->execute([$id]);
$payments = $stmt_pembayaran->fetchAll();

$total_bayar = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'Berhasil') {
        $total_bayar += (float)$p['jumlah_bayar'];
    }
}
$sisa_tagihan = max(0, (float)$tagihan['total_tagihan'] - $total_bayar);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tagihan & Setoran Pajak - <?= htmlspecialchars($tagihan['nomor_tagihan']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fdfdfd; }
        .kop-header { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 24px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="no-print mb-4 text-end">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
        Cetak Surat Tagihan (SSP)
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm ms-2">
        Tutup
    </button>
</div>

<div class="container" style="max-width: 800px; border: 1px solid #ccc; padding: 30px; background: #fff;">
    <!-- KOP RESMI -->
    <div class="kop-header text-center">
        <h5 class="fw-bold mb-0 text-uppercase">KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h5>
        <h6 class="fw-bold mb-1 text-uppercase">DIREKTORAT JENDERAL PAJAK</h6>
        <p class="small mb-0">SURAT SETORAN PAJAK ELEKTRONIK (e-Billing)</p>
        <p class="small mb-0 text-muted fst-italic">Lembar Konfirmasi Tagihan & Setoran Kas Negara</p>
    </div>

    <div class="text-center mb-4">
        <h6 class="fw-bold text-decoration-underline mb-1">SURAT TAGIHAN PAJAK (BILLING)</h6>
        <div class="fs-5 font-monospace fw-bold text-dark mt-1">KODE BILLING: <?= htmlspecialchars($tagihan['nomor_tagihan']) ?></div>
        <span class="badge <?= $tagihan['status'] === 'Lunas' ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-1 mt-1">
            STATUS: <?= strtoupper($tagihan['status']) ?>
        </span>
    </div>

    <table class="table table-bordered table-sm small mb-4">
        <tbody>
            <tr>
                <td style="width: 35%;" class="fw-bold bg-light">Wajib Pajak</td>
                <td class="fw-bold"><?= htmlspecialchars($tagihan['nama_wp']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">NPWP</td>
                <td class="font-monospace fw-bold"><?= htmlspecialchars($tagihan['npwp']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Alamat Wajib Pajak</td>
                <td><?= htmlspecialchars($tagihan['alamat_wp'] ?: '-') ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Jenis Pajak / Akun Pajak</td>
                <td>[<?= htmlspecialchars($tagihan['kode_pajak'] ?: '-') ?>] <?= htmlspecialchars($tagihan['nama_pajak']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Masa & Tahun Pajak</td>
                <td>Tahun <?= htmlspecialchars($tagihan['tahun_pajak']) ?> (<?= htmlspecialchars($tagihan['ket_masa'] ?: '-') ?>)</td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Tanggal Terbit Tagihan</td>
                <td><?= format_tanggal($tagihan['tanggal_tagihan']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Batas Waktu Jatuh Tempo</td>
                <td class="text-danger fw-bold"><?= format_tanggal($tagihan['tanggal_jatuh_tempo']) ?></td>
            </tr>
            <tr class="table-light">
                <td class="fw-bold fs-6">Total Tagihan Pajak Terutang</td>
                <td class="fw-bold fs-6 text-dark"><?= format_rupiah($tagihan['total_tagihan']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Total Pembayaran Diterima</td>
                <td class="text-success fw-bold"><?= format_rupiah($total_bayar) ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Sisa Kewajiban Belum Dibayar</td>
                <td class="text-danger fw-bold"><?= format_rupiah($sisa_tagihan) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Histori Transaksi Pembayaran -->
    <h6 class="fw-bold small mb-2"><i class="bi bi-clock-history"></i> Riwayat Transaksi Pembayaran</h6>
    <table class="table table-bordered table-sm small mb-4">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Tanggal Bayar</th>
                <th>Nomor Referensi Bank</th>
                <th>Metode</th>
                <th>Jumlah Bayar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="text-center text-muted">Belum ada transaksi pembayaran tercatat.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($payments as $pay): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= format_tanggal($pay['tanggal_pembayaran']) ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($pay['nomor_referensi']) ?></td>
                    <td><?= htmlspecialchars($pay['metode_pembayaran']) ?></td>
                    <td class="fw-bold text-success"><?= format_rupiah($pay['jumlah_bayar']) ?></td>
                    <td><span class="badge <?= $pay['status'] === 'Berhasil' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars($pay['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="row mt-5 pt-3 small text-center">
        <div class="col-6">
            <p class="mb-5">Mengetahui Wajib Pajak,</p>
            <strong class="d-block text-decoration-underline"><?= htmlspecialchars($tagihan['nama_wp']) ?></strong>
            <span class="text-muted font-monospace"><?= htmlspecialchars($tagihan['npwp']) ?></span>
        </div>
        <div class="col-6">
            <p class="mb-5">Petugas Bendahara Penerima Pajak,</p>
            <strong class="d-block text-decoration-underline">Bendahara Penerimaan Negara</strong>
            <span class="text-muted">NIP. 19850415 200812 1 002</span>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.75rem;">
        <em>Simpan bukti tagihan dan pembayaran ini sebagai arsip resmi administrasi perpajakan yang sah.</em>
    </div>
</div>

</body>
</html>

