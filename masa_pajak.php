<?php
$page_title = 'Kelola Masa Pajak';
$active_nav = 'masa_pajak';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

$bulan_nama = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun_pajak = (int)($_POST['tahun_pajak'] ?? date('Y'));
    $bulan_pajak = !empty($_POST['bulan_pajak']) ? (int)$_POST['bulan_pajak'] : null;
    $tanggal_mulai = !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $tanggal_akhir = !empty($_POST['tanggal_akhir']) ? $_POST['tanggal_akhir'] : null;
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    if ($tahun_pajak < 2000 || $tahun_pajak > 2100) {
        $error = 'Tahun pajak tidak valid!';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO masa_pajak (tahun_pajak, bulan_pajak, tanggal_mulai, tanggal_akhir, keterangan)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$tahun_pajak, $bulan_pajak, $tanggal_mulai, $tanggal_akhir, $keterangan]);
                set_flash_message('success', "Masa Pajak berhasil ditambahkan!");
                header("Location: masa_pajak.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE masa_pajak 
                    SET tahun_pajak = ?, bulan_pajak = ?, tanggal_mulai = ?, tanggal_akhir = ?, keterangan = ?
                    WHERE id_masa_pajak = ?
                ");
                $stmt->execute([$tahun_pajak, $bulan_pajak, $tanggal_mulai, $tanggal_akhir, $keterangan, $id]);
                set_flash_message('success', "Data Masa Pajak berhasil diperbarui!");
                header("Location: masa_pajak.php");
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
        $stmt = $db->prepare("DELETE FROM masa_pajak WHERE id_masa_pajak = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Masa Pajak berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus! Masa pajak ini masih digunakan dalam data pelaporan SPT.");
    }
    header("Location: masa_pajak.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM masa_pajak WHERE id_masa_pajak = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Masa Pajak tidak ditemukan.");
        header("Location: masa_pajak.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("SELECT * FROM masa_pajak ORDER BY tahun_pajak DESC, bulan_pajak DESC, id_masa_pajak DESC")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-calendar3 text-tax-primary me-2"></i>6. Tabel Masa Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan periode atau jangka waktu yang menjadi dasar pelaporan pajak.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="masa_pajak.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="masa_pajak.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Tambah Masa Pajak</a>
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
                <?= $action === 'create' ? '<i class="bi bi-calendar-plus text-success me-2"></i>Tambah Masa Pajak Baru' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Masa Pajak' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tahun Pajak <span class="text-danger">*</span></label>
                        <input type="number" name="tahun_pajak" class="form-control" min="2000" max="2100" 
                               value="<?= htmlspecialchars($_POST['tahun_pajak'] ?? $row_data['tahun_pajak'] ?? date('Y')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Bulan Pajak (Masa)</label>
                        <select name="bulan_pajak" class="form-select">
                            <option value="">-- Tahunan (Tanpa Bulan) --</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <?php 
                                    $currBulan = $_POST['bulan_pajak'] ?? $row_data['bulan_pajak'] ?? '';
                                    $selected = ($currBulan !== '' && (int)$currBulan === $m) ? 'selected' : '';
                                ?>
                                <option value="<?= $m ?>" <?= $selected ?>><?= $m ?> - <?= $bulan_nama[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="form-text">Pilih bulan untuk SPT Masa bulanan, atau kosongkan untuk SPT Tahunan.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Mulai Periode</label>
                        <input type="date" name="tanggal_mulai" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? $row_data['tanggal_mulai'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Akhir Periode</label>
                        <input type="date" name="tanggal_akhir" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_akhir'] ?? $row_data['tanggal_akhir'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan / Label Masa</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Masa Pajak Januari 2026 / SPT Tahunan 2025" 
                               value="<?= htmlspecialchars($_POST['keterangan'] ?? $row_data['keterangan'] ?? '') ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Masa Pajak' : 'Perbarui Masa' ?>
                        </button>
                        <a href="masa_pajak.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- LIST DATA MASA PAJAK -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari tahun atau keterangan...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Periode Masa Pajak
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Tahun</th>
                        <th>Bulan / Masa</th>
                        <th>Keterangan</th>
                        <th>Rentang Tanggal</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data Masa Pajak.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_masa_pajak'] ?></span></td>
                            <td><span class="badge bg-dark fs-6"><?= htmlspecialchars($row['tahun_pajak']) ?></span></td>
                            <td>
                                <?php if (!empty($row['bulan_pajak'])): ?>
                                    <strong class="text-tax-primary"><?= $bulan_nama[(int)$row['bulan_pajak']] ?? $row['bulan_pajak'] ?></strong>
                                    <small class="text-muted">(Bulan <?= $row['bulan_pajak'] ?>)</small>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark">Tahunan (Penuh)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="fw-semibold text-dark"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></span></td>
                            <td>
                                <span class="small text-muted">
                                    <?= format_tanggal($row['tanggal_mulai']) ?> &mdash; <?= format_tanggal($row['tanggal_akhir']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="masa_pajak.php?action=edit&id=<?= $row['id_masa_pajak'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="masa_pajak.php?action=delete&id=<?= $row['id_masa_pajak'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Masa Pajak <?= htmlspecialchars($row['tahun_pajak']) ?>" title="Hapus">
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

