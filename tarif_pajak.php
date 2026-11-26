<?php
$page_title = 'Kelola Tarif Pajak';
$active_nav = 'tarif_pajak';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Get Jenis Pajak for Dropdown
$jenis_pajak_list = [];
if ($dbReady) {
    try {
        $jenis_pajak_list = $db->query("SELECT id_jenis_pajak, kode_pajak, nama_pajak FROM jenis_pajak ORDER BY nama_pajak ASC")->fetchAll();
    } catch (Exception $e) {}
}

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jenis_pajak = (int)($_POST['id_jenis_pajak'] ?? 0);
    $tarif = (float)str_replace(',', '.', $_POST['tarif'] ?? 0);
    $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $tanggal_akhir = !empty($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : null;

    if ($id_jenis_pajak <= 0) {
        $error = 'Silakan pilih Jenis Pajak!';
    } elseif ($tarif <= 0) {
        $error = 'Nilai tarif harus lebih besar dari 0!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO tarif_pajak (id_jenis_pajak, tarif, tanggal_mulai, tanggal_akhir)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id_jenis_pajak, $tarif, $tanggal_mulai, $tanggal_akhir]);
                set_flash_message('success', "Tarif pajak <strong>{$tarif}%</strong> berhasil ditambahkan!");
                header("Location: tarif_pajak.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE tarif_pajak 
                    SET id_jenis_pajak = ?, tarif = ?, tanggal_mulai = ?, tanggal_akhir = ?
                    WHERE id_tarif = ?
                ");
                $stmt->execute([$id_jenis_pajak, $tarif, $tanggal_mulai, $tanggal_akhir, $id]);
                set_flash_message('success', "Data Tarif pajak berhasil diperbarui!");
                header("Location: tarif_pajak.php");
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
        $stmt = $db->prepare("DELETE FROM tarif_pajak WHERE id_tarif = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Tarif Pajak berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus tarif pajak.");
    }
    header("Location: tarif_pajak.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM tarif_pajak WHERE id_tarif = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Tarif Pajak tidak ditemukan.");
        header("Location: tarif_pajak.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("
            SELECT tp.*, jp.nama_pajak, jp.kode_pajak
            FROM tarif_pajak tp
            JOIN jenis_pajak jp ON tp.id_jenis_pajak = jp.id_jenis_pajak
            ORDER BY tp.id_tarif DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-percent text-tax-primary me-2"></i>4. Tabel Tarif Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan informasi tarif berdasarkan jenis pajak dan periode berlakunya.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="tarif_pajak.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="tarif_pajak.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Tarif Pajak</a>
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
                <?= $action === 'create' ? '<i class="bi bi-plus-circle text-success me-2"></i>Tambah Tarif Pajak Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Tarif Pajak' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pilih Jenis Pajak <span class="text-danger">*</span></label>
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
                        <label class="form-label fw-semibold">Besaran Tarif (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="tarif" class="form-control" placeholder="Contoh: 5.00 atau 11.00" 
                                   value="<?= htmlspecialchars($_POST['tarif'] ?? $row_data['tarif'] ?? '') ?>" required>
                            <span class="input-group-text fw-bold">%</span>
                        </div>
                        <div class="form-text">Persentase tarif pemotongan/pemungutan pajak.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Mulai Berlaku</label>
                        <input type="date" name="tanggal_mulai" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? $row_data['tanggal_mulai'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Berakhir Berlaku</label>
                        <input type="date" name="tanggal_akhir" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_akhir'] ?? $row_data['tanggal_akhir'] ?? '') ?>">
                        <div class="form-text">Kosongkan jika masih berlaku seterusnya tanpa batas.</div>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Tarif' : 'Perbarui Tarif' ?>
                        </button>
                        <a href="tarif_pajak.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA TARIF PAJAK -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari nama jenis pajak atau tarif...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Aturan Tarif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Jenis Pajak</th>
                        <th>Tarif Pajak</th>
                        <th>Periode Mulai</th>
                        <th>Periode Berakhir</th>
                        <th>Status Masa</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data Tarif Pajak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <?php
                            $today = date('Y-m-d');
                            $is_active = true;
                            if ($row['tanggal_mulai'] && $row['tanggal_mulai'] > $today) $is_active = false;
                            if ($row['tanggal_akhir'] && $row['tanggal_akhir'] < $today) $is_active = false;
                        ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_tarif'] ?></span></td>
                            <td>
                                <span class="badge bg-dark font-monospace me-1"><?= htmlspecialchars($row['kode_pajak'] ?: '-') ?></span>
                                <strong class="text-dark"><?= htmlspecialchars($row['nama_pajak']) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-success fs-6 fw-bold px-3 py-1"><?= number_format($row['tarif'], 2) ?>%</span>
                            </td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_mulai']) ?></span></td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_akhir']) ?></span></td>
                            <td>
                                <?php if ($is_active): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Berlaku</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border">Kadaluarsa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="tarif_pajak.php?action=edit&id=<?= $row['id_tarif'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="tarif_pajak.php?action=delete&id=<?= $row['id_tarif'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Tarif <?= number_format($row['tarif'], 2) ?>%" title="Hapus">
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

