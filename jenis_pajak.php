<?php
$page_title = 'Kelola Jenis Pajak';
$active_nav = 'jenis_pajak';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pajak = strtoupper(sanitize($_POST['kode_pajak'] ?? ''));
    $nama_pajak = sanitize($_POST['nama_pajak'] ?? '');
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    if (empty($nama_pajak)) {
        $error = 'Nama Pajak wajib diisi!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO jenis_pajak (kode_pajak, nama_pajak, keterangan)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$kode_pajak, $nama_pajak, $keterangan]);
                set_flash_message('success', "Jenis Pajak <strong>$nama_pajak</strong> berhasil ditambahkan!");
                header("Location: jenis_pajak.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE jenis_pajak 
                    SET kode_pajak = ?, nama_pajak = ?, keterangan = ?
                    WHERE id_jenis_pajak = ?
                ");
                $stmt->execute([$kode_pajak, $nama_pajak, $keterangan, $id]);
                set_flash_message('success', "Data Jenis Pajak <strong>$nama_pajak</strong> berhasil diperbarui!");
                header("Location: jenis_pajak.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Kode Pajak <strong>$kode_pajak</strong> sudah terdaftar! Gunakan kode unik lain.";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM jenis_pajak WHERE id_jenis_pajak = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Jenis Pajak berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus! Jenis pajak ini masih digunakan dalam data Tarif, Objek, atau SPT.");
    }
    header("Location: jenis_pajak.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM jenis_pajak WHERE id_jenis_pajak = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Jenis Pajak tidak ditemukan.");
        header("Location: jenis_pajak.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("SELECT * FROM jenis_pajak ORDER BY id_jenis_pajak ASC")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-tags-fill text-tax-primary me-2"></i>3. Tabel Jenis Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan kategori pajak yang dikelola sistem, seperti PPh, PPN, dan PBB.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="jenis_pajak.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="jenis_pajak.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Jenis Pajak</a>
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
                <?= $action === 'create' ? '<i class="bi bi-tag text-success me-2"></i>Tambah Jenis Pajak Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Jenis Pajak' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kode Pajak</label>
                        <input type="text" name="kode_pajak" class="form-control text-uppercase" placeholder="Contoh: PPH-21, PPN, PBB-P2" 
                               value="<?= htmlspecialchars($_POST['kode_pajak'] ?? $row_data['kode_pajak'] ?? '') ?>">
                        <div class="form-text">Kode unik kategori pajak (opsional/unik).</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Pajak <span class="text-danger">*</span></label>
                        <input type="text" name="nama_pajak" class="form-control" placeholder="Contoh: Pajak Penghasilan Pasal 21" 
                               value="<?= htmlspecialchars($_POST['nama_pajak'] ?? $row_data['nama_pajak'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan / Deskripsi</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Penjelasan mengenai dasar pengenaan jenis pajak ini"><?= htmlspecialchars($_POST['keterangan'] ?? $row_data['keterangan'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Jenis Pajak' : 'Perbarui Data' ?>
                        </button>
                        <a href="jenis_pajak.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA JENIS PAJAK -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari kode atau nama pajak...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Kategori Pajak
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Kode Pajak</th>
                        <th>Nama Pajak</th>
                        <th>Keterangan</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data Jenis Pajak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_jenis_pajak'] ?></span></td>
                            <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['kode_pajak'] ?: '-') ?></span></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($row['nama_pajak']) ?></div>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></span></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="jenis_pajak.php?action=edit&id=<?= $row['id_jenis_pajak'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="jenis_pajak.php?action=delete&id=<?= $row['id_jenis_pajak'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Jenis Pajak <?= htmlspecialchars($row['nama_pajak']) ?>" title="Hapus">
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

