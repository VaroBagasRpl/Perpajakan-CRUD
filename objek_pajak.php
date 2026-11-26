<?php
$page_title = 'Kelola Objek Pajak';
$active_nav = 'objek_pajak';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Dropdown options
$wajib_pajak_list = [];
$jenis_pajak_list = [];
if ($dbReady) {
    try {
        $wajib_pajak_list = $db->query("SELECT id_wajib_pajak, npwp, nama FROM wajib_pajak WHERE status = 'Aktif' ORDER BY nama ASC")->fetchAll();
        $jenis_pajak_list = $db->query("SELECT id_jenis_pajak, kode_pajak, nama_pajak FROM jenis_pajak ORDER BY nama_pajak ASC")->fetchAll();
    } catch (Exception $e) {}
}

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_wajib_pajak = (int)($_POST['id_wajib_pajak'] ?? 0);
    $id_jenis_pajak = (int)($_POST['id_jenis_pajak'] ?? 0);
    $nama_objek = sanitize($_POST['nama_objek'] ?? '');
    $alamat_objek = sanitize($_POST['alamat_objek'] ?? '');
    
    // Clean currency formatted value
    $nilai_raw = str_replace(['Rp', '.', ' '], '', $_POST['nilai_objek'] ?? '0');
    $nilai_objek = (float)str_replace(',', '.', $nilai_raw);

    if ($id_wajib_pajak <= 0 || $id_jenis_pajak <= 0) {
        $error = 'Wajib Pajak dan Jenis Pajak wajib dipilih!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO objek_pajak (id_wajib_pajak, id_jenis_pajak, nama_objek, alamat_objek, nilai_objek)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_wajib_pajak, $id_jenis_pajak, $nama_objek, $alamat_objek, $nilai_objek]);
                set_flash_message('success', "Objek Pajak <strong>$nama_objek</strong> berhasil ditambahkan!");
                header("Location: objek_pajak.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE objek_pajak 
                    SET id_wajib_pajak = ?, id_jenis_pajak = ?, nama_objek = ?, alamat_objek = ?, nilai_objek = ?
                    WHERE id_objek_pajak = ?
                ");
                $stmt->execute([$id_wajib_pajak, $id_jenis_pajak, $nama_objek, $alamat_objek, $nilai_objek, $id]);
                set_flash_message('success', "Data Objek Pajak berhasil diperbarui!");
                header("Location: objek_pajak.php");
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
        $stmt = $db->prepare("DELETE FROM objek_pajak WHERE id_objek_pajak = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Objek Pajak berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus objek pajak.");
    }
    header("Location: objek_pajak.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM objek_pajak WHERE id_objek_pajak = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Objek Pajak tidak ditemukan.");
        header("Location: objek_pajak.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("
            SELECT op.*, wp.nama AS nama_wp, wp.npwp, jp.nama_pajak, jp.kode_pajak
            FROM objek_pajak op
            JOIN wajib_pajak wp ON op.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON op.id_jenis_pajak = jp.id_jenis_pajak
            ORDER BY op.id_objek_pajak DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-building-check text-tax-primary me-2"></i>5. Tabel Objek Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan informasi objek yang berkaitan dengan kewajiban perpajakan wajib pajak.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="objek_pajak.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="objek_pajak.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Objek Pajak</a>
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
                <?= $action === 'create' ? '<i class="bi bi-building-add text-success me-2"></i>Tambah Objek Pajak Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Objek Pajak' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pemilik / Wajib Pajak <span class="text-danger">*</span></label>
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
                        <label class="form-label fw-semibold">Jenis Pajak Terkait <span class="text-danger">*</span></label>
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
                        <label class="form-label fw-semibold">Nama Objek Pajak</label>
                        <input type="text" name="nama_objek" class="form-control" placeholder="Contoh: Gaji Pegawai / Usaha Kafe / Tanah & Ruko" 
                               value="<?= htmlspecialchars($_POST['nama_objek'] ?? $row_data['nama_objek'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nilai Objek / Nilai Jual / Omzet (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" name="nilai_objek" class="form-control input-currency" placeholder="0" 
                                   value="<?= number_format((float)($_POST['nilai_objek'] ?? $row_data['nilai_objek'] ?? 0), 0, ',', '.') ?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Alamat / Lokasi Objek Pajak</label>
                        <textarea name="alamat_objek" class="form-control" rows="3" placeholder="Lokasi fisik atau alamat usaha objek pajak"><?= htmlspecialchars($_POST['alamat_objek'] ?? $row_data['alamat_objek'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Objek' : 'Perbarui Objek' ?>
                        </button>
                        <a href="objek_pajak.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA OBJEK PAJAK -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari nama objek, WP, jenis...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Objek Terdaftar
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Wajib Pajak</th>
                        <th>Jenis Pajak</th>
                        <th>Nama Objek</th>
                        <th>Nilai Objek</th>
                        <th>Alamat Objek</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data Objek Pajak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_objek_pajak'] ?></span></td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($row['nama_wp']) ?></strong>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($row['npwp']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark border"><?= htmlspecialchars($row['nama_pajak']) ?></span>
                            </td>
                            <td><strong class="text-tax-primary"><?= htmlspecialchars($row['nama_objek'] ?: '-') ?></strong></td>
                            <td><span class="fw-bold text-success"><?= format_rupiah($row['nilai_objek']) ?></span></td>
                            <td><span class="small text-muted"><?= htmlspecialchars($row['alamat_objek'] ?: '-') ?></span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="objek_pajak.php?action=edit&id=<?= $row['id_objek_pajak'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="objek_pajak.php?action=delete&id=<?= $row['id_objek_pajak'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Objek Pajak <?= htmlspecialchars($row['nama_objek']) ?>" title="Hapus">
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

