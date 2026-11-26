<?php
$page_title = 'Kelola Petugas Pajak';
$active_nav = 'petugas';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_petugas = sanitize($_POST['nama_petugas'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $jabatan = sanitize($_POST['jabatan'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($nama_petugas) || empty($username)) {
        $error = 'Nama Petugas dan Username wajib diisi!';
    } else {
        try {
            if ($action === 'create') {
                if (empty($password)) {
                    $error = 'Password wajib diisi untuk petugas baru!';
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        INSERT INTO petugas (nama_petugas, username, password, jabatan, email)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$nama_petugas, $username, $password_hash, $jabatan, $email]);
                    set_flash_message('success', "Petugas <strong>$nama_petugas</strong> berhasil didaftarkan!");
                    header("Location: petugas.php");
                    exit;
                }
            } elseif ($action === 'edit' && $id > 0) {
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        UPDATE petugas 
                        SET nama_petugas = ?, username = ?, password = ?, jabatan = ?, email = ?
                        WHERE id_petugas = ?
                    ");
                    $stmt->execute([$nama_petugas, $username, $password_hash, $jabatan, $email, $id]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE petugas 
                        SET nama_petugas = ?, username = ?, jabatan = ?, email = ?
                        WHERE id_petugas = ?
                    ");
                    $stmt->execute([$nama_petugas, $username, $jabatan, $email, $id]);
                }
                set_flash_message('success', "Data Petugas <strong>$nama_petugas</strong> berhasil diperbarui!");
                header("Location: petugas.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username <strong>$username</strong> sudah digunakan! Silakan pilih username lain.";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM petugas WHERE id_petugas = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Data Petugas berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus! Petugas ini masih terhubung dengan riwayat verifikasi SPT.");
    }
    header("Location: petugas.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM petugas WHERE id_petugas = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Petugas tidak ditemukan.");
        header("Location: petugas.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("SELECT * FROM petugas ORDER BY id_petugas DESC")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-person-badge-fill text-tax-primary me-2"></i>2. Tabel Petugas</h1>
        <p class="text-muted small mb-0">Menyimpan data administrator atau petugas yang mengelola sistem perpajakan.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="petugas.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="petugas.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Petugas</a>
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
                <?= $action === 'create' ? '<i class="bi bi-person-plus text-success me-2"></i>Tambah Petugas Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Petugas' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Lengkap Petugas <span class="text-danger">*</span></label>
                        <input type="text" name="nama_petugas" class="form-control" placeholder="Contoh: Drs. Hendra Gunawan, M.Si" 
                               value="<?= htmlspecialchars($_POST['nama_petugas'] ?? $row_data['nama_petugas'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="Username untuk login" 
                               value="<?= htmlspecialchars($_POST['username'] ?? $row_data['username'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Password <?= $action === 'edit' ? '<span class="text-muted small fw-normal">(Kosongkan jika tidak diubah)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" name="password" class="form-control" placeholder="<?= $action === 'edit' ? 'Ketik password baru jika ingin mengubah' : 'Password aman' ?>" 
                               <?= $action === 'create' ? 'required' : '' ?>>
                        <div class="form-text">Password dienkripsi secara otomatis menggunakan algoritma BCRYPT Hash.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jabatan / Role</label>
                        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Pemeriksa Pajak / Account Representative" 
                               value="<?= htmlspecialchars($_POST['jabatan'] ?? $row_data['jabatan'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Petugas</label>
                        <input type="email" name="email" class="form-control" placeholder="petugas@kemenkeu.go.id" 
                               value="<?= htmlspecialchars($_POST['email'] ?? $row_data['email'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Petugas' : 'Perbarui Data' ?>
                        </button>
                        <a href="petugas.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA PETUGAS -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari nama, username, jabatan...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Petugas
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nama Petugas</th>
                        <th>Username</th>
                        <th>Jabatan</th>
                        <th>Email</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data Petugas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_petugas'] ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_petugas']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($row['username']) ?></span></td>
                            <td><span class="text-muted small"><?= htmlspecialchars($row['jabatan'] ?: '-') ?></span></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="small text-decoration-none">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($row['email'] ?: '-') ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="petugas.php?action=edit&id=<?= $row['id_petugas'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="petugas.php?action=delete&id=<?= $row['id_petugas'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Petugas <?= htmlspecialchars($row['nama_petugas']) ?>" title="Hapus">
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

