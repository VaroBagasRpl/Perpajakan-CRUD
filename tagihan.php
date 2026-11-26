<?php
$page_title = 'Kelola Tagihan Pajak';
$active_nav = 'tagihan';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$action = $_GET['action'] ?? 'index';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// Get Available SPT for Tagihan (SPT that don't have a tagihan yet, or current SPT if editing)
$spt_options = [];
if ($dbReady) {
    try {
        $spt_query = "
            SELECT s.id_spt, s.jumlah_pajak, wp.nama AS nama_wp, jp.nama_pajak, mp.tahun_pajak
            FROM spt s
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            JOIN masa_pajak mp ON s.id_masa_pajak = mp.id_masa_pajak
            WHERE s.id_spt NOT IN (SELECT id_spt FROM tagihan " . ($id > 0 ? "WHERE id_tagihan != $id" : "") . ")
            ORDER BY s.id_spt DESC
        ";
        $spt_options = $db->query($spt_query)->fetchAll();
    } catch (Exception $e) {}
}

// Handle Form Submissions (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_spt = (int)($_POST['id_spt'] ?? 0);
    $nomor_tagihan = sanitize($_POST['nomor_tagihan'] ?? '');
    $tanggal_tagihan = !empty($_POST['tanggal_tagihan']) ? $_POST['tanggal_tagihan'] : date('Y-m-d');
    $tanggal_jatuh_tempo = !empty($_POST['tanggal_jatuh_tempo']) ? $_POST['tanggal_jatuh_tempo'] : date('Y-m-d', strtotime('+30 days'));
    
    $total_raw = str_replace(['Rp', '.', ' '], '', $_POST['total_tagihan'] ?? '0');
    $total_tagihan = (float)str_replace(',', '.', $total_raw);

    $status = in_array($_POST['status'] ?? '', ['Belum Lunas', 'Sebagian', 'Lunas']) ? $_POST['status'] : 'Belum Lunas';

    if ($id_spt <= 0) {
        $error = 'Silakan pilih SPT rujukan tagihan!';
    } elseif ($total_tagihan <= 0) {
        $error = 'Total tagihan harus lebih dari Rp 0!';
    } else {
        if (empty($nomor_tagihan)) {
            $nomor_tagihan = 'BILL-' . date('Ym') . '-' . str_pad($id_spt, 4, '0', STR_PAD_LEFT);
        }

        try {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO tagihan (id_spt, nomor_tagihan, tanggal_tagihan, tanggal_jatuh_tempo, total_tagihan, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_spt, $nomor_tagihan, $tanggal_tagihan, $tanggal_jatuh_tempo, $total_tagihan, $status]);
                set_flash_message('success', "Tagihan <strong>$nomor_tagihan</strong> berhasil diterbitkan!");
                header("Location: tagihan.php");
                exit;
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $db->prepare("
                    UPDATE tagihan 
                    SET id_spt = ?, nomor_tagihan = ?, tanggal_tagihan = ?, tanggal_jatuh_tempo = ?, total_tagihan = ?, status = ?
                    WHERE id_tagihan = ?
                ");
                $stmt->execute([$id_spt, $nomor_tagihan, $tanggal_tagihan, $tanggal_jatuh_tempo, $total_tagihan, $status, $id]);
                set_flash_message('success', "Tagihan <strong>$nomor_tagihan</strong> berhasil diperbarui!");
                header("Location: tagihan.php");
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Nomor tagihan atau SPT sudah terhubung dengan tagihan lain!";
            } else {
                $error = "Terjadi kesalahan database: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM tagihan WHERE id_tagihan = ?");
        $stmt->execute([$id]);
        set_flash_message('success', "Data Tagihan berhasil dihapus!");
    } catch (PDOException $e) {
        set_flash_message('danger', "Gagal menghapus! Tagihan ini memiliki data histori pembayaran.");
    }
    header("Location: tagihan.php");
    exit;
}

// Data for Edit
$row_data = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM tagihan WHERE id_tagihan = ?");
    $stmt->execute([$id]);
    $row_data = $stmt->fetch();
    if (!$row_data) {
        set_flash_message('danger', "Data Tagihan tidak ditemukan.");
        header("Location: tagihan.php");
        exit;
    }
}

// List Data
$data_list = [];
if ($dbReady) {
    try {
        $data_list = $db->query("
            SELECT t.*, 
                   wp.nama AS nama_wp, wp.npwp,
                   jp.nama_pajak,
                   COALESCE(SUM(p.jumlah_bayar), 0) AS total_sudah_dibayar
            FROM tagihan t
            JOIN spt s ON t.id_spt = s.id_spt
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            LEFT JOIN pembayaran p ON t.id_tagihan = p.id_tagihan AND p.status = 'Berhasil'
            GROUP BY t.id_tagihan
            ORDER BY t.id_tagihan DESC
        ")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-receipt-cutoff text-tax-primary me-2"></i>8. Tabel Tagihan Pajak</h1>
        <p class="text-muted small mb-0">Menyimpan kewajiban pembayaran pajak berdasarkan laporan SPT.</p>
    </div>
    <div>
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <a href="tagihan.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali ke Daftar</a>
        <?php else: ?>
            <a href="tagihan.php?action=create" class="btn btn-tax-primary btn-sm"><i class="bi bi-plus-lg"></i> Terbitkan Tagihan Baru</a>
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
                <?= $action === 'create' ? '<i class="bi bi-receipt text-success me-2"></i>Terbitkan Tagihan Pajak (Billing)' : '<i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Tagihan' ?>
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Rujukan SPT Pajak <span class="text-danger">*</span></label>
                        <select name="id_spt" id="selectSPT" class="form-select" required>
                            <option value="">-- Pilih SPT untuk Ditagihkan --</option>
                            <?php foreach ($spt_options as $s): ?>
                                <?php 
                                    $selected = (($row_data['id_spt'] ?? $_POST['id_spt'] ?? '') == $s['id_spt']) ? 'selected' : '';
                                ?>
                                <option value="<?= $s['id_spt'] ?>" data-jumlah="<?= $s['jumlah_pajak'] ?>" <?= $selected ?>>
                                    SPT #<?= $s['id_spt'] ?> - <?= htmlspecialchars($s['nama_wp']) ?> (<?= htmlspecialchars($s['nama_pajak']) ?>) - <?= format_rupiah($s['jumlah_pajak']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Tagihan (Kode Billing)</label>
                        <input type="text" name="nomor_tagihan" class="form-control" placeholder="Biarkan kosong untuk generate otomatis" 
                               value="<?= htmlspecialchars($_POST['nomor_tagihan'] ?? $row_data['nomor_tagihan'] ?? '') ?>">
                        <div class="form-text">Contoh: BILL-202602-0001 (Unik).</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Tagihan</label>
                        <input type="date" name="tanggal_tagihan" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_tagihan'] ?? $row_data['tanggal_tagihan'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Jatuh Tempo</label>
                        <input type="date" name="tanggal_jatuh_tempo" class="form-control" 
                               value="<?= htmlspecialchars($_POST['tanggal_jatuh_tempo'] ?? $row_data['tanggal_jatuh_tempo'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Total Tagihan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" name="total_tagihan" id="inputTotalTagihan" class="form-control input-currency" placeholder="0" 
                                   value="<?= number_format((float)($_POST['total_tagihan'] ?? $row_data['total_tagihan'] ?? 0), 0, ',', '.') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status Pembayaran Tagihan</label>
                        <select name="status" class="form-select">
                            <option value="Belum Lunas" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Belum Lunas') ? 'selected' : '' ?>>Belum Lunas</option>
                            <option value="Sebagian" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Sebagian') ? 'selected' : '' ?>>Sebagian</option>
                            <option value="Lunas" <?= (($_POST['status'] ?? $row_data['status'] ?? '') === 'Lunas') ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-tax-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> <?= $action === 'create' ? 'Terbitkan Tagihan' : 'Perbarui Tagihan' ?>
                        </button>
                        <a href="tagihan.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('selectSPT').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var jumlah = selectedOption.getAttribute('data-jumlah');
            if (jumlah) {
                document.getElementById('inputTotalTagihan').value = new Intl.NumberFormat('id-ID').format(jumlah);
            }
        });
    </script>

<?php else: ?>
    <!-- LIST DATA TAGIHAN -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari nomor billing, nama WP...">
            </div>
            <div class="small text-muted">
                Total: <strong><?= count($data_list) ?></strong> Tagihan
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-searchable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nomor Tagihan</th>
                        <th>Wajib Pajak</th>
                        <th>Jenis Pajak</th>
                        <th>Tgl Terbit</th>
                        <th>Jatuh Tempo</th>
                        <th>Total Tagihan</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada data Tagihan. Terbitkan tagihan baru dari SPT.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= $row['id_tagihan'] ?></span></td>
                            <td>
                                <strong class="text-tax-primary font-monospace"><?= htmlspecialchars($row['nomor_tagihan']) ?></strong>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= htmlspecialchars($row['nama_wp']) ?></strong>
                                <small class="text-muted font-monospace"><?= htmlspecialchars($row['npwp']) ?></small>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars($row['nama_pajak']) ?></span></td>
                            <td><span class="small text-muted"><?= format_tanggal($row['tanggal_tagihan']) ?></span></td>
                            <td>
                                <?php
                                    $is_overdue = ($row['status'] !== 'Lunas' && $row['tanggal_jatuh_tempo'] < date('Y-m-d'));
                                ?>
                                <span class="small <?= $is_overdue ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= format_tanggal($row['tanggal_jatuh_tempo']) ?>
                                    <?= $is_overdue ? '<i class="bi bi-exclamation-triangle" title="Jatuh tempo terlewati!"></i>' : '' ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= format_rupiah($row['total_tagihan']) ?></div>
                                <?php if ($row['total_sudah_dibayar'] > 0 && $row['status'] !== 'Lunas'): ?>
                                    <small class="text-success d-block">Dibayar: <?= format_rupiah($row['total_sudah_dibayar']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= render_status_badge($row['status']) ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <?php if ($row['status'] !== 'Lunas'): ?>
                                        <a href="pembayaran.php?action=create&id_tagihan=<?= $row['id_tagihan'] ?>" class="btn btn-outline-success" title="Catat Bayar">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="cetak_tagihan.php?id=<?= $row['id_tagihan'] ?>" target="_blank" class="btn btn-outline-secondary" title="Cetak Surat Setoran / Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="tagihan.php?action=edit&id=<?= $row['id_tagihan'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="tagihan.php?action=delete&id=<?= $row['id_tagihan'] ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-name="Tagihan <?= htmlspecialchars($row['nomor_tagihan']) ?>" title="Hapus">
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

