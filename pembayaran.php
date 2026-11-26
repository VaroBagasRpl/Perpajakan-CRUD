<?php
$page_title = 'Kelola Pembayaran Pajak';
$active_nav = 'pembayaran';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preset_tagihan_id = isset($_GET['id_tagihan']) ? (int)$_GET['id_tagihan'] : 0;
$error = null;

// Get Active Tagihan for Dropdown
$tagihan_list = [];
if ($dbReady) {
    try {
        $tagihan_list = $db->query("
            SELECT t.id_tagihan, t.nomor_tagihan, t.total_tagihan, t.status, wp.nama AS nama_wp, jp.nama_pajak
            FROM tagihan t
            JOIN spt s ON t.id_spt = s.id_spt
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            ORDER BY t.id_tagihan DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}

/**
 * Helper to recalculate and update tagihan status based on payments
 */
function updateTagihanStatus($db, $tagihan_id) {
    $stmt = $db->prepare("SELECT total_tagihan FROM tagihan WHERE id_tagihan = ?");
    $stmt->execute([$tagihan_id]);
    $total_tagihan = (float)$stmt->fetchColumn();

    $stmt_pay = $db->prepare("SELECT SUM(jumlah_bayar) FROM pembayaran WHERE id_tagihan = ? AND status = 'Berhasil'");
    $stmt_pay->execute([$tagihan_id]);
    $total_dibayar = (float)$stmt_pay->fetchColumn();

    $new_status = 'Belum Lunas';
    if ($total_dibayar >= $total_tagihan && $total_tagihan > 0) {
        $new_status = 'Lunas';
    } elseif ($total_dibayar > 0) {
        $new_status = 'Sebagian';
    }

    $update = $db->prepare("UPDATE tagihan SET status = ? WHERE id_tagihan = ?");
    $update->execute([$new_status, $tagihan_id]);
}

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tagihan = (int)($_POST['id_tagihan'] ?? 0);
    $tanggal_pembayaran = !empty($_POST['tanggal_pembayaran']) ? $_POST['tanggal_pembayaran'] : date('Y-m-d');
    
    $bayar_raw = str_replace(['Rp', '.', ' '], '', $_POST['jumlah_bayar'] ?? '0');
    $jumlah_bayar = (float)str_replace(',', '.', $bayar_raw);

    $metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? '');
    $nomor_referensi = sanitize($_POST['nomor_referensi'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['Berhasil', 'Menunggu', 'Gagal']) ? $_POST['status'] : 'Menunggu';

    if ($id_tagihan <= 0) {
        $error = 'Silakan pilih tagihan yang dibayarkan!';
    } elseif ($jumlah_bayar <= 0) {
        $error = 'Jumlah bayar harus lebih dari Rp 0!';
    } else {
        if (empty($nomor_referensi)) {
            $nomor_referensi = 'TRX-' . strtoupper(substr(md5(uniqid()), 0, 10));
        }

        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO pembayaran (id_tagihan, tanggal_pembayaran, jumlah_bayar, metode_pembayaran, nomor_referensi, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_tagihan, $tanggal_pembayaran, $jumlah_bayar, $metode_pembayaran, $nomor_referensi, $status]);
                
                updateTagihanStatus($db, $id_tagihan);

                set_flash_message('success', "Transaksi pembayaran <strong>$nomor_referensi</strong> berhasil dicatat!");
                header("Location: pembayaran.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE pembayaran 
                    SET id_tagihan = ?, tanggal_pembayaran = ?, jumlah_bayar = ?, metode_pembayaran = ?, nomor_referensi = ?, status = ?
                    WHERE id_pembayaran = ?
                ");
                $stmt->execute([$id_tagihan, $tanggal_pembayaran, $jumlah_bayar, $metode_pembayaran, $nomor_referensi, $status, $id]);
                
                updateTagihanStatus($db, $id_tagihan);

                set_flash_message('success', "Data pembayaran berhasil diperbarui!");
                header("Location: pembayaran.php");
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
        $stmt_get = $db->prepare("SELECT id_tagihan FROM pembayaran WHERE id_pembayaran = ?");
        $stmt_get->execute([$id]);
        $tagihan_id = $stmt_get->fetchColumn();

        $stmt = $db->prepare("DELETE FROM pembayaran WHERE id_pembayaran = ?");
        $stmt->execute([$id]);

        if ($tagihan_id) {
            updateTagihanStatus($db, $tagihan_id);
        }

        set_flash_message('success', "Data Pembayaran berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus pembayaran.");
    }
    header("Location: pembayaran.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM pembayaran WHERE id_pembayaran = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Pembayaran tidak ditemukan.");
        header("Location: pembayaran.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("
            SELECT p.*, 
                   t.nomor_tagihan, t.total_tagihan,
                   wp.nama AS nama_wp, wp.npwp,
                   jp.nama_pajak
            FROM pembayaran p
            JOIN tagihan t ON p.id_tagihan = t.id_tagihan
            JOIN spt s ON t.id_spt = s.id_spt
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            ORDER BY p.id_pembayaran DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-credit-card-2-front-fill text-tax-primary me-2"></i>9. Tabel Pembayaran</h1>
        <p class="text-muted small mb-0">Mencatat transaksi pembayaran untuk tagihan pajak.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="pembayaran.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="pembayaran.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Catat Pembayaran Baru</a>
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
                <?= $action === 'create' ? '<i class="bi bi-cash-stack text-success me-2"></i>Catat Transaksi Pembayaran Pajak' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Pembayaran' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pilih Tagihan Pajak (Billing) <span class="text-danger">*</span></label>
                        <select name="id_tagihan" id="selectTagihan" class="form-select" required>
                            <option value="">-- Pilih Tagihan yang Dibayarkan --</option>
                            <?php foreach ($tagihan_list as $t): ?>
                                <?php 
                                    $curr_sel = $row_data['id_tagihan'] ?? $_POST['id_tagihan'] ?? $preset_tagihan_id;
                                    $selected = ($curr_sel == $t['id_tagihan']) ? 'selected' : '';
                                ?>
                                <option value="<?= $t['id_tagihan'] ?>" data-total="<?= $t['total_tagihan'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($t['nomor_tagihan']) ?> &mdash; <?= htmlspecialchars($t['nama_wp']) ?> (<?= format_rupiah($t['total_tagihan']) ?>) [Status: <?= $t['status'] ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jumlah yang Dibayar (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" name="jumlah_bayar" id="inputJumlahBayar" class="form-control input-currency" placeholder="0" 
                                   value="<?= number_format((float)($_POST['jumlah_bayar'] ?? $row_data['jumlah_bayar'] ?? 0), 0, ',', '.') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Setor / Pembayaran</label>
                        <input type="date" name="tanggal_pembayaran" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_pembayaran'] ?? $row_data['tanggal_pembayaran'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Metode Pembayaran</label>
                        <select name="metode_pembayaran" class="form-select">
                            <?php 
                                $methods = ['Bank Transfer (VA)', 'Bank BCA', 'Bank Mandiri', 'Bank BRI', 'Bank BNI', 'Teller Pos / Bank', 'QRIS', 'Lainnya'];
                                $curr_method = $_POST['metode_pembayaran'] ?? $row_data['metode_pembayaran'] ?? 'Bank Transfer (VA)';
                            ?>
                            <?php foreach ($methods as $m): ?>
                                <option value="<?= $m ?>" <?= ($curr_method === $m) ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nomor Referensi Transaksi Bank</label>
                        <input type="text" name="nomor_referensi" class="form-control" placeholder="Contoh: TRX-BCA-987123" 
                               value="<?= htmlspecialchars($_POST['nomor_referensi'] ?? $row_data['nomor_referensi'] ?? '') ?>">
                        <div class="form-text">Kosongkan untuk nomor referensi otomatis.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status Transaksi</label>
                        <select name="status" class="form-select">
                            <option value="Berhasil" <?= (($_POST['status'] ?? $row_data['status'] ?? 'Berhasil') === 'Berhasil') ? 'selected' : '' ?>>Berhasil</option>
                            <option value="Menunggu" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Menunggu') ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Gagal" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Gagal') ? 'selected' : '' ?>>Gagal</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Simpan Bukti Bayar' : 'Perbarui Pembayaran' ?>
                        </button>
                        <a href="pembayaran.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('selectTagihan').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var total = selectedOption.getAttribute('data-total');
            if (total && document.getElementById('inputJumlahBayar').value == '0') {
                document.getElementById('inputJumlahBayar').value = new Intl.NumberFormat('id-ID').format(total);
            }
        });
    </script>

<?php else: ?>
    <!-- LIST DATA PEMBAYARAN -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari ref, no billing, nama WP...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Transaksi Masuk
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>No. Referensi Bank</th>
                        <th>No. Tagihan</th>
                        <th>Wajib Pajak</th>
                        <th>Tgl Pembayaran</th>
                        <th>Jumlah Bayar</th>
                        <th>Metode Bayar</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data Pembayaran. Catat pembayaran untuk melunasi tagihan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_pembayaran'] ?></span></td>
                            <td>
                                <strong class="text-dark font-monospace"><?= htmlspecialchars($row['nomor_referensi'] ?: '-') ?></strong>
                            </td>
                            <td>
                                <a href="tagihan.php?search=<?= urlencode($row['nomor_tagihan']) ?>" class="badge bg-light text-tax-primary border text-decoration-none">
                                    <?= htmlspecialchars($row['nomor_tagihan']) ?>
                                </a>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($row['nama_wp']) ?></strong>
                                <small class="text-muted"><?= htmlspecialchars($row['nama_pajak']) ?></small>
                            </td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_pembayaran']) ?></span></td>
                            <td><strong class="text-success"><?= format_rupiah($row['jumlah_bayar']) ?></strong></td>
                            <td><span class="badge bg-secondary-subtle text-dark border"><?= htmlspecialchars($row['metode_pembayaran'] ?: 'Lainnya') ?></span></td>
                            <td><?= render_status_badge($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="cetak_tagihan.php?id=<?= $row['id_tagihan'] ?>" target="_blank" class="btn btn-outline-secondary" title="Bukti Setoran">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="pembayaran.php?action=edit&id=<?= $row['id_pembayaran'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="pembayaran.php?action=delete&id=<?= $row['id_pembayaran'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Pembayaran <?= htmlspecialchars($row['nomor_referensi']) ?>" title="Hapus">
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

