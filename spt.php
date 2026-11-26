<?php
$page_title = 'Kelola SPT (Surat Pemberitahuan)';
$active_nav = 'spt';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Get Master Data for Dropdowns
$wajib_pajak_list = [];
$jenis_pajak_list = [];
$masa_pajak_list = [];
$petugas_list = [];

if ($dbReady) {
    try {
        $wajib_pajak_list = $db->query("SELECT id_wajib_pajak, npwp, nama FROM wajib_pajak WHERE status = 'Aktif' ORDER BY nama ASC")->fetchAll();
        $jenis_pajak_list = $db->query("SELECT id_jenis_pajak, kode_pajak, nama_pajak FROM jenis_pajak ORDER BY nama_pajak ASC")->fetchAll();
        $masa_pajak_list = $db->query("SELECT id_masa_pajak, tahun_pajak, bulan_pajak, keterangan FROM masa_pajak ORDER BY tahun_pajak DESC, bulan_pajak DESC")->fetchAll();
        $petugas_list = $db->query("SELECT id_petugas, nama_petugas, jabatan FROM petugas ORDER BY nama_petugas ASC")->fetchAll();
    } catch (Exception $e) {}
}

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_wajib_pajak = (int)($_POST['id_wajib_pajak'] ?? 0);
    $id_jenis_pajak = (int)($_POST['id_jenis_pajak'] ?? 0);
    $id_masa_pajak = (int)($_POST['id_masa_pajak'] ?? 0);
    $id_petugas = !empty($_POST['id_petugas']) ? (int)$_POST['id_petugas'] : null;
    $tanggal_lapor = !empty($_POST['tanggal_lapor']) ? $_POST['tanggal_lapor'] : date('Y-m-d');
    
    $dpp_raw = str_replace(['Rp', '.', ' '], '', $_POST['dasar_pengenaan'] ?? '0');
    $dasar_pengenaan = (float)str_replace(',', '.', $dpp_raw);

    $pajak_raw = str_replace(['Rp', '.', ' '], '', $_POST['jumlah_pajak'] ?? '0');
    $jumlah_pajak = (float)str_replace(',', '.', $pajak_raw);

    $status = in_array($_POST['status'] ?? '', ['Draft', 'Diajukan', 'Diverifikasi', 'Ditolak']) ? $_POST['status'] : 'Draft';

    if ($id_wajib_pajak <= 0 || $id_jenis_pajak <= 0 || $id_masa_pajak <= 0) {
        $error = 'Wajib Pajak, Jenis Pajak, dan Masa Pajak wajib dipilih!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO spt (id_wajib_pajak, id_jenis_pajak, id_masa_pajak, id_petugas, tanggal_lapor, dasar_pengenaan, jumlah_pajak, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_wajib_pajak, $id_jenis_pajak, $id_masa_pajak, $id_petugas, $tanggal_lapor, $dasar_pengenaan, $jumlah_pajak, $status]);
                $new_spt_id = $db->lastInsertId();

                // Jika status Diverifikasi, otomatis buat tagihan jika belum ada
                if ($status === 'Diverifikasi' && $jumlah_pajak > 0) {
                    $nomor_tagihan = 'BILL-' . date('Ym') . '-' . str_pad($new_spt_id, 4, '0', STR_PAD_LEFT);
                    $jatuh_tempo = date('Y-m-d', strtotime('+30 days'));
                    $stmt_tagihan = $db->prepare("
                        INSERT INTO tagihan (id_spt, nomor_tagihan, tanggal_tagihan, tanggal_jatuh_tempo, total_tagihan, status)
                        VALUES (?, ?, ?, ?, ?, 'Belum Lunas')
                    ");
                    $stmt_tagihan->execute([$new_spt_id, $nomor_tagihan, $tanggal_lapor, $jatuh_tempo, $jumlah_pajak]);
                }

                set_flash_message('success', "Laporan SPT berhasil disimpan!");
                header("Location: spt.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE spt 
                    SET id_wajib_pajak = ?, id_jenis_pajak = ?, id_masa_pajak = ?, id_petugas = ?, tanggal_lapor = ?, dasar_pengenaan = ?, jumlah_pajak = ?, status = ?
                    WHERE id_spt = ?
                ");
                $stmt->execute([$id_wajib_pajak, $id_jenis_pajak, $id_masa_pajak, $id_petugas, $tanggal_lapor, $dasar_pengenaan, $jumlah_pajak, $status, $id]);
                
                // Jika diedit menjadi Diverifikasi, cek apakah tagihan sudah ada
                if ($status === 'Diverifikasi' && $jumlah_pajak > 0) {
                    $cek_tagihan = $db->prepare("SELECT id_tagihan FROM tagihan WHERE id_spt = ?");
                    $cek_tagihan->execute([$id]);
                    if (!$cek_tagihan->fetch()) {
                        $nomor_tagihan = 'BILL-' . date('Ym') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
                        $jatuh_tempo = date('Y-m-d', strtotime('+30 days'));
                        $stmt_tagihan = $db->prepare("
                            INSERT INTO tagihan (id_spt, nomor_tagihan, tanggal_tagihan, tanggal_jatuh_tempo, total_tagihan, status)
                            VALUES (?, ?, ?, ?, ?, 'Belum Lunas')
                        ");
                        $stmt_tagihan->execute([$id, $nomor_tagihan, $tanggal_lapor, $jatuh_tempo, $jumlah_pajak]);
                    }
                }

                set_flash_message('success', "Data SPT berhasil diperbarui!");
                header("Location: spt.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM spt WHERE id_spt = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Laporan SPT berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus SPT! SPT ini masih memiliki data Tagihan atau Pembayaran.");
    }
    header("Location: spt.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM spt WHERE id_spt = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data SPT tidak ditemukan.");
        header("Location: spt.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("
            SELECT s.*, 
                   wp.nama AS nama_wp, wp.npwp,
                   jp.nama_pajak, jp.kode_pajak,
                   mp.tahun_pajak, mp.bulan_pajak, mp.keterangan AS ket_masa,
                   p.nama_petugas,
                   t.id_tagihan, t.nomor_tagihan, t.status AS status_tagihan
            FROM spt s
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            JOIN masa_pajak mp ON s.id_masa_pajak = mp.id_masa_pajak
            LEFT JOIN petugas p ON s.id_petugas = p.id_petugas
            LEFT JOIN tagihan t ON s.id_spt = t.id_spt
            ORDER BY s.id_spt DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-file-earmark-text-fill text-tax-primary me-2"></i>7. Tabel SPT (Surat Pemberitahuan)</h1>
        <p class="text-muted small mb-0">Mencatat laporan Surat Pemberitahuan yang disampaikan oleh wajib pajak.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="spt.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="spt.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Buat Laporan SPT</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- FORM CREATE / EDIT -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-dark">
                <?= $action === 'create' ? '<i class="bi bi-file-earmark-plus text-success me-2"></i>Input SPT Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data SPT' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Wajib Pajak Pelapor <span class="text-danger">*</span></label>
                        <select name="id_wajib_pajak" class="form-select" required>
                            <option value="">-- Pilih Wajib Pajak --</option>
                            <?php foreach ($wajib_pajak_list as $wp): ?>
                                <?php 
                                    $selected = (($row_data['id_wajib_pajak'] ?? $_POST['id_wajib_pajak'] ?? '') == $wp['id_wajib_pajak']) ? 'selected' : '';
                                ?>
                                <option value="<?= $wp['id_wajib_pajak'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($wp['nama']) ?> (NPWP: <?= htmlspecialchars($wp['npwp']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Pajak Dilaporkan <span class="text-danger">*</span></label>
                        <select name="id_jenis_pajak" class="form-select" required>
                            <option value="">-- Pilih Jenis Pajak --</option>
                            <?php foreach ($jenis_pajak_list as $jp): ?>
                                <?php 
                                    $selected = (($row_data['id_jenis_pajak'] ?? $_POST['id_jenis_pajak'] ?? '') == $jp['id_jenis_pajak']) ? 'selected' : '';
                                ?>
                                <option value="<?= $jp['id_jenis_pajak'] ?>" <?= $selected ?>>
                                    [<?= htmlspecialchars($jp['kode_pajak'] ?: '-') ?>] <?= htmlspecialchars($jp['nama_pajak']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Masa / Periode Pajak <span class="text-danger">*</span></label>
                        <select name="id_masa_pajak" class="form-select" required>
                            <option value="">-- Pilih Masa Pajak --</option>
                            <?php foreach ($masa_pajak_list as $mp): ?>
                                <?php 
                                    $selected = (($row_data['id_masa_pajak'] ?? $_POST['id_masa_pajak'] ?? '') == $mp['id_masa_pajak']) ? 'selected' : '';
                                    $lbl = 'Tahun ' . $mp['tahun_pajak'] . ($mp['bulan_pajak'] ? ' - Masa ' . $mp['bulan_pajak'] : ' (Tahunan)');
                                    if ($mp['keterangan']) $lbl .= ' - ' . $mp['keterangan'];
                                ?>
                                <option value="<?= $mp['id_masa_pajak'] ?>" <?= $selected ?>><?= htmlspecialchars($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Petugas Pemeriksa / Verifikator</label>
                        <select name="id_petugas" class="form-select">
                            <option value="">-- Belum Ditugaskan / Otomatis --</option>
                            <?php foreach ($petugas_list as $pet): ?>
                                <?php 
                                    $selected = (($row_data['id_petugas'] ?? $_POST['id_petugas'] ?? '') == $pet['id_petugas']) ? 'selected' : '';
                                ?>
                                <option value="<?= $pet['id_petugas'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($pet['nama_petugas']) ?> (<?= htmlspecialchars($pet['jabatan'] ?: 'Staf') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Lapor</label>
                        <input type="date" name="tanggal_lapor" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_lapor'] ?? $row_data['tanggal_lapor'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Dasar Pengenaan Pajak (DPP)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" name="dasar_pengenaan" class="form-control input-currency" placeholder="0" 
                                   value="<?= number_format((float)($_POST['dasar_pengenaan'] ?? $row_data['dasar_pengenaan'] ?? 0), 0, ',', '.') ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jumlah Pajak Terutang</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" name="jumlah_pajak" class="form-control input-currency" placeholder="0" 
                                   value="<?= number_format((float)($_POST['jumlah_pajak'] ?? $row_data['jumlah_pajak'] ?? 0), 0, ',', '.') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status SPT</label>
                        <select name="status" class="form-select">
                            <?php foreach (['Draft', 'Diajukan', 'Diverifikasi', 'Ditolak'] as $st): ?>
                                <?php 
                                    $selected = (($row_data['status'] ?? $_POST['status'] ?? 'Draft') === $st) ? 'selected' : '';
                                ?>
                                <option value="<?= $st ?>" <?= $selected ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Status 'Diverifikasi' akan otomatis menerbitkan tagihan/billing untuk pembayaran.</div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan & Proses SPT' : 'Perbarui SPT' ?>
                        </button>
                        <a href="spt.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA SPT -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari nama WP, jenis, status...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Laporan SPT
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Wajib Pajak</th>
                        <th>Jenis & Masa Pajak</th>
                        <th>Tgl Lapor</th>
                        <th>Dasar Pengenaan</th>
                        <th>Pajak Terutang</th>
                        <th>Status</th>
                        <th>Billing Tagihan</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data SPT. Klik 'Buat Laporan SPT' untuk memulai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_spt'] ?></span></td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($row['nama_wp']) ?></strong>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($row['npwp']) ?></small>
                            </td>
                            <td>
                                <div><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['kode_pajak'] ?: '-') ?></span> <?= htmlspecialchars($row['nama_pajak']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($row['ket_masa'] ?: ('Masa ' . $row['bulan_pajak'] . '/' . $row['tahun_pajak'])) ?></small>
                            </td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_lapor']) ?></span></td>
                            <td><span class="small text-muted"><?= format_rupiah($row['dasar_pengenaan']) ?></span></td>
                            <td><strong class="text-tax-primary"><?= format_rupiah($row['jumlah_pajak']) ?></strong></td>
                            <td><?= render_status_badge($row['status']) ?></td>
                            <td>
                                <?php if ($row['id_tagihan']): ?>
                                    <a href="tagihan.php?search=<?= urlencode($row['nomor_tagihan']) ?>" class="badge bg-light text-dark border text-decoration-none">
                                        <i class="bi bi-receipt"></i> <?= htmlspecialchars($row['nomor_tagihan']) ?>
                                    </a>
                                    <div class="mt-1"><?= render_status_badge($row['status_tagihan']) ?></div>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-muted">Belum Ditagih</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="cetak_spt.php?id=<?= $row['id_spt'] ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Bukti SPT">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="spt.php?action=edit&id=<?= $row['id_spt'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="spt.php?action=delete&id=<?= $row['id_spt'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="SPT #<?= $row['id_spt'] ?> - <?= htmlspecialchars($row['nama_wp']) ?>" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

