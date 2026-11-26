<?php
$page_title = 'Kelola Wajib Pajak';
$active_nav = 'wajib_pajak';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $npwp = sanitize($_POST['npwp'] ?? '');
    $nama = sanitize($_POST['nama'] ?? '');
    $nik = sanitize($_POST['nik'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    $tanggal_daftar = !empty($_POST['tanggal_daftar']) ? $_POST['tanggal_daftar'] : date('Y-m-d');
    $status = in_array($_POST['status'] ?? '', ['Aktif', 'Nonaktif']) ? $_POST['status'] : 'Aktif';

    if (empty($npwp) || empty($nama)) {
        $error = 'NPWP dan Nama Wajib Pajak wajib diisi!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO wajib_pajak (npwp, nama, nik, alamat, email, no_telepon, tanggal_daftar, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$npwp, $nama, $nik, $alamat, $email, $no_telepon, $tanggal_daftar, $status]);
                set_flash_message('success', "Wajib Pajak <strong>$nama</strong> berhasil ditambahkan!");
                header("Location: wajib_pajak.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE wajib_pajak 
                    SET npwp = ?, nama = ?, nik = ?, alamat = ?, email = ?, no_telepon = ?, tanggal_daftar = ?, status = ?
                    WHERE id_wajib_pajak = ?
                ");
                $stmt->execute([$npwp, $nama, $nik, $alamat, $email, $no_telepon, $tanggal_daftar, $status, $id]);
                set_flash_message('success', "Data Wajib Pajak <strong>$nama</strong> berhasil diperbarui!");
                header("Location: wajib_pajak.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "NPWP <strong>$npwp</strong> sudah terdaftar pada sistem! Gunakan NPWP lain.";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM wajib_pajak WHERE id_wajib_pajak = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Data Wajib Pajak berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus! Data ini masih berelasi dengan data Objek Pajak / SPT.");
    }
    header("Location: wajib_pajak.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM wajib_pajak WHERE id_wajib_pajak = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Wajib Pajak tidak ditemukan.");
        header("Location: wajib_pajak.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("SELECT * FROM wajib_pajak ORDER BY id_wajib_pajak DESC")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-people-fill text-tax-primary me-2"></i>1. Tabel Wajib Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan identitas orang pribadi atau badan yang terdaftar sebagai wajib pajak.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="wajib_pajak.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="wajib_pajak.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Wajib Pajak</a>
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
                <?= $action === 'create' ? '<i class="bi bi-person-plus text-success me-2"></i>Tambah Wajib Pajak Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Wajib Pajak' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">NPWP <span class="text-danger">*</span></label>
                        <input type="text" name="npwp" class="form-control" placeholder="Contoh: 01.234.567.8-012.000" 
                               value="<?= htmlspecialchars($_POST['npwp'] ?? $row_data['npwp'] ?? '') ?>" required>
                        <div class="form-text">Nomor Pokok Wajib Pajak (Harus Unik).</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Lengkap / Nama Badan <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: Budi Santoso / PT Maju Sejahtera" 
                               value="<?= htmlspecialchars($_POST['nama'] ?? $row_data['nama'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">NIK (Nomor Induk Kependudukan)</label>
                        <input type="text" name="nik" class="form-control" placeholder="16 digit NIK KTP" 
                               value="<?= htmlspecialchars($_POST['nik'] ?? $row_data['nik'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No. Telepon / WhatsApp</label>
                        <input type="text" name="no_telepon" class="form-control" placeholder="Contoh: 081234567890" 
                               value="<?= htmlspecialchars($_POST['no_telepon'] ?? $row_data['no_telepon'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input type="email" name="email" class="form-control" placeholder="wajibpajak@domain.com" 
                               value="<?= htmlspecialchars($_POST['email'] ?? $row_data['email'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Daftar</label>
                        <input type="date" name="tanggal_daftar" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_daftar'] ?? $row_data['tanggal_daftar'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status Wajib Pajak</label>
                        <select name="status" class="form-select">
                            <option value="Aktif" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Aktif') ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat domisili wajib pajak"><?= htmlspecialchars($_POST['alamat'] ?? $row_data['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Wajib Pajak' : 'Perbarui Data' ?>
                        </button>
                        <a href="wajib_pajak.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA WAJIB PAJAK -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari NPWP, nama, NIK...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Wajib Pajak
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>NPWP</th>
                        <th>Nama Wajib Pajak</th>
                        <th>NIK</th>
                        <th>Kontak & Email</th>
                        <th>Tgl Daftar</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data Wajib Pajak. Klik 'Tambah Wajib Pajak' untuk membuat baru.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_wajib_pajak'] ?></span></td>
                            <td><strong class="text-tax-primary font-monospace"><?= htmlspecialchars($row['npwp']) ?></strong></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;"><?= htmlspecialchars($row['alamat'] ?? '-') ?></small>
                            </td>
                            <td><span class="font-monospace small"><?= htmlspecialchars($row['nik'] ?: '-') ?></span></td>
                            <td>
                                <div class="small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($row['no_telepon'] ?: '-') ?></div>
                                <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($row['email'] ?: '-') ?></div>
                            </td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_daftar']) ?></span></td>
                            <td><?= render_status_badge($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="wajib_pajak.php?action=edit&id=<?= $row['id_wajib_pajak'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="wajib_pajak.php?action=delete&id=<?= $row['id_wajib_pajak'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Wajib Pajak <?= htmlspecialchars($row['nama']) ?>" title="Hapus">
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

