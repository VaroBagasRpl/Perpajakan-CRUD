<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$db || $id <= 0) {
    die("Data SPT tidak valid atau database belum terhubung.");
}

$stmt = $db->prepare("
    SELECT s.*, 
           wp.nama AS nama_wp, wp.npwp, wp.nik, wp.alamat AS alamat_wp, wp.email, wp.no_telepon,
           jp.nama_pajak, jp.kode_pajak,
           mp.tahun_pajak, mp.bulan_pajak, mp.keterangan AS ket_masa,
           p.nama_petugas, p.jabatan
    FROM spt s
    JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
    JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
    JOIN masa_pajak mp ON s.id_masa_pajak = mp.id_masa_pajak
    LEFT JOIN petugas p ON s.id_petugas = p.id_petugas
    WHERE s.id_spt = ?
");
$stmt->execute([$id]);
$spt = $stmt->fetch();

if (!$spt) {
    die("Laporan SPT #$id tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pelaporan SPT - <?= htmlspecialchars($spt['nama_wp']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; background: #fdfdfd; }
        .kop-header { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 24px; }
        .table-custom td { padding: 6px 10px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="no-print mb-4 text-end">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
        Cetak Bukti SPT
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
        <p class="small mb-0">KANTOR PELAYANAN PAJAK PRATAMA</p>
        <p class="small mb-0 text-muted fst-italic">Sistem Informasi Perpajakan Terpadu &bull; Tanda Bukti Penerimaan Surat Pemberitahuan (SPT)</p>
    </div>

    <div class="text-center mb-4">
        <h6 class="fw-bold text-decoration-underline mb-1">BUKTI PENERIMAAN ELEKTRONIK (BPE)</h6>
        <span class="small font-monospace text-muted">NOMOR BUKTI: BPE/SPT/<?= date('Ym', strtotime($spt['tanggal_lapor'] ?? 'now')) ?>/<?= str_pad($spt['id_spt'], 5, '0', STR_PAD_LEFT) ?></span>
    </div>

    <p class="small mb-3">Telah diterima Surat Pemberitahuan (SPT) dengan data rincian sebagai berikut:</p>

    <table class="table table-bordered table-sm small mb-4">
        <tbody>
            <tr>
                <td style="width: 35%;" class="fw-bold bg-light">Nomor Pokok Wajib Pajak (NPWP)</td>
                <td class="font-monospace fw-bold"><?= htmlspecialchars($spt['npwp']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Nama Wajib Pajak</td>
                <td><?= htmlspecialchars($spt['nama_wp']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Nomor Induk Kependudukan (NIK)</td>
                <td><?= htmlspecialchars($spt['nik'] ?: '-') ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Alamat Domisili</td>
                <td><?= htmlspecialchars($spt['alamat_wp'] ?: '-') ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Jenis Pajak</td>
                <td>[<?= htmlspecialchars($spt['kode_pajak'] ?: '-') ?>] <?= htmlspecialchars($spt['nama_pajak']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Tahun / Masa Pajak</td>
                <td>
                    Tahun <?= htmlspecialchars($spt['tahun_pajak']) ?>
                    <?= $spt['bulan_pajak'] ? ' - Masa Pajak Bulan ' . $spt['bulan_pajak'] : ' (Tahunan)' ?>
                    (<?= htmlspecialchars($spt['ket_masa'] ?: '-') ?>)
                </td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Tanggal Lapor</td>
                <td><?= format_tanggal($spt['tanggal_lapor']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Dasar Pengenaan Pajak (DPP)</td>
                <td><?= format_rupiah($spt['dasar_pengenaan']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Jumlah Pajak Terutang</td>
                <td class="fw-bold fs-6"><?= format_rupiah($spt['jumlah_pajak']) ?></td>
            </tr>
            <tr>
                <td class="fw-bold bg-light">Status Laporan SPT</td>
                <td>
                    <span class="badge bg-dark text-white"><?= htmlspecialchars($spt['status']) ?></span>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="row mt-5 pt-3 small text-center">
        <div class="col-6">
            <p class="mb-5">Wajib Pajak / Kuasa,</p>
            <strong class="d-block text-decoration-underline"><?= htmlspecialchars($spt['nama_wp']) ?></strong>
            <span class="text-muted font-monospace"><?= htmlspecialchars($spt['npwp']) ?></span>
        </div>
        <div class="col-6">
            <p class="mb-0">Diterima & Diverifikasi oleh:</p>
            <p class="mb-5">Petugas Pelayanan Pajak,</p>
            <strong class="d-block text-decoration-underline"><?= htmlspecialchars($spt['nama_petugas'] ?: 'Admin Pelayanan Terpadu') ?></strong>
            <span class="text-muted"><?= htmlspecialchars($spt['jabatan'] ?: 'Pemeriksa Pajak') ?></span>
        </div>
    </div>

    <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.75rem;">
        <em>Dokumen ini dicetak otomatis oleh Sistem Informasi Perpajakan dan merupakan bukti resmi pelaporan perpajakan.</em>
    </div>
</div>

</body>
</html>

