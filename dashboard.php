<?php
$page_title = 'Dashboard Perpajakan';
$active_nav = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$counts = [
    'wp_aktif' => 0,
    'wp_total' => 0,
    'petugas' => 0,
    'jenis_pajak' => 0,
    'objek_pajak' => 0,
    'spt_total' => 0,
    'spt_verifikasi' => 0,
    'tagihan_lunas' => 0,
    'tagihan_belum' => 0,
    'total_penerimaan' => 0
];

$recent_spt = [];
$recent_bayar = [];

if ($dbReady) {
    try {
        $db = getDB();
        $counts['wp_total'] = $db->query("SELECT COUNT(*) FROM wajib_pajak")->fetchColumn() ?: 0;
        $counts['wp_aktif'] = $db->query("SELECT COUNT(*) FROM wajib_pajak WHERE status = 'Aktif'")->fetchColumn() ?: 0;
        $counts['petugas'] = $db->query("SELECT COUNT(*) FROM petugas")->fetchColumn() ?: 0;
        $counts['jenis_pajak'] = $db->query("SELECT COUNT(*) FROM jenis_pajak")->fetchColumn() ?: 0;
        $counts['objek_pajak'] = $db->query("SELECT COUNT(*) FROM objek_pajak")->fetchColumn() ?: 0;
        $counts['spt_total'] = $db->query("SELECT COUNT(*) FROM spt")->fetchColumn() ?: 0;
        $counts['spt_verifikasi'] = $db->query("SELECT COUNT(*) FROM spt WHERE status = 'Diverifikasi'")->fetchColumn() ?: 0;
        $counts['tagihan_lunas'] = $db->query("SELECT COUNT(*) FROM tagihan WHERE status = 'Lunas'")->fetchColumn() ?: 0;
        $counts['tagihan_belum'] = $db->query("SELECT COUNT(*) FROM tagihan WHERE status != 'Lunas'")->fetchColumn() ?: 0;
        $counts['total_penerimaan'] = $db->query("SELECT SUM(jumlah_bayar) FROM pembayaran WHERE status = 'Berhasil'")->fetchColumn() ?: 0;

        // Recent SPT
        $recent_spt = $db->query("
            SELECT s.*, wp.nama AS nama_wp, jp.nama_pajak 
            FROM spt s
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            JOIN jenis_pajak jp ON s.id_jenis_pajak = jp.id_jenis_pajak
            ORDER BY s.id_spt DESC LIMIT 5
        ")->fetchAll();

        // Recent Pembayaran
        $recent_bayar = $db->query("
            SELECT p.*, t.nomor_tagihan, wp.nama AS nama_wp
            FROM pembayaran p
            JOIN tagihan t ON p.id_tagihan = t.id_tagihan
            JOIN spt s ON t.id_spt = s.id_spt
            JOIN wajib_pajak wp ON s.id_wajib_pajak = wp.id_wajib_pajak
            ORDER BY p.id_pembayaran DESC LIMIT 5
        ")->fetchAll();
    } catch (Exception $e) {
        // Silently continue
    }
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title">Dashboard Sistem Perpajakan</h1>
        <p class="text-muted small mb-0">Selamat datang di Sistem Informasi Manajemen Perpajakan Terpadu.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="wajib_pajak.php?action=create" class="btn btn-sm btn-tax-primary">
            <i class="bi bi-person-plus-fill me-1"></i> + Wajib Pajak
        </a>
        <a href="spt.php?action=create" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-plus me-1"></i> + Buat SPT
        </a>
        <a href="pembayaran.php?action=create" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cash-coin me-1"></i> + Catat Bayar
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Wajib Pajak -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Wajib Pajak Aktif</span>
                    <h3 class="fw-bold my-1 text-dark"><?= number_format($counts['wp_aktif']) ?></h3>
                    <small class="text-muted">Dari total <?= number_format($counts['wp_total']) ?> terdaftar</small>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <hr class="my-2">
            <a href="wajib_pajak.php" class="text-tax-primary small text-decoration-none fw-bold d-flex align-items-center justify-content-between">
                <span>Kelola Data WP</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- SPT Terverifikasi -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">SPT Diverifikasi</span>
                    <h3 class="fw-bold my-1 text-info"><?= number_format($counts['spt_verifikasi']) ?></h3>
                    <small class="text-muted">Total SPT: <?= number_format($counts['spt_total']) ?></small>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-file-earmark-check-fill"></i>
                </div>
            </div>
            <hr class="my-2">
            <a href="spt.php" class="text-tax-primary small text-decoration-none fw-bold d-flex align-items-center justify-content-between">
                <span>Lihat Laporan SPT</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Tagihan Lunas -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Tagihan Lunas</span>
                    <h3 class="fw-bold my-1 text-success"><?= number_format($counts['tagihan_lunas']) ?></h3>
                    <small class="text-danger">Belum Lunas: <?= number_format($counts['tagihan_belum']) ?></small>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
            </div>
            <hr class="my-2">
            <a href="tagihan.php" class="text-tax-primary small text-decoration-none fw-bold d-flex align-items-center justify-content-between">
                <span>Lihat Tagihan</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Total Penerimaan -->
    <div class="col-sm-6 col-xl-3">
        <div class="card card-stat p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">Total Kas Pajak Masuk</span>
                    <h4 class="fw-bold my-1 text-tax-primary"><?= format_rupiah($counts['total_penerimaan']) ?></h4>
                    <small class="text-success"><i class="bi bi-check-circle"></i> Transaksi Berhasil</small>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <hr class="my-2">
            <a href="pembayaran.php" class="text-tax-primary small text-decoration-none fw-bold d-flex align-items-center justify-content-between">
                <span>Rincian Pembayaran</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- 9 Database Entities Quick Grid -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-3-fill text-tax-primary me-2"></i>9 Tabel Database Perpajakan (CRUD)</h5>
        <span class="badge bg-tax-primary">Relasi Lengkap MySQL</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-2">
            <div class="col-md-4 col-sm-6">
                <a href="wajib_pajak.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-people me-1 text-tax-primary"></i> 1. wajib_pajak</span>
                    <span class="badge bg-secondary"><?= $counts['wp_total'] ?></span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="petugas.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-person-badge me-1 text-tax-primary"></i> 2. petugas</span>
                    <span class="badge bg-secondary"><?= $counts['petugas'] ?></span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="jenis_pajak.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-tags me-1 text-tax-primary"></i> 3. jenis_pajak</span>
                    <span class="badge bg-secondary"><?= $counts['jenis_pajak'] ?></span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="tarif_pajak.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-percent me-1 text-tax-primary"></i> 4. tarif_pajak</span>
                    <span class="badge bg-secondary">Tarif</span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="objek_pajak.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-building me-1 text-tax-primary"></i> 5. objek_pajak</span>
                    <span class="badge bg-secondary"><?= $counts['objek_pajak'] ?></span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="masa_pajak.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-calendar3 me-1 text-tax-primary"></i> 6. masa_pajak</span>
                    <span class="badge bg-secondary">Periode</span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="spt.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-file-earmark-text me-1 text-tax-primary"></i> 7. spt</span>
                    <span class="badge bg-secondary"><?= $counts['spt_total'] ?></span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="tagihan.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-receipt me-1 text-tax-primary"></i> 8. tagihan</span>
                    <span class="badge bg-secondary">Billing</span>
                </a>
            </div>
            <div class="col-md-4 col-sm-6">
                <a href="pembayaran.php" class="p-2 border rounded-3 d-flex align-items-center justify-content-between text-decoration-none bg-light hover-bg">
                    <span class="small fw-bold text-dark"><i class="bi bi-credit-card me-1 text-tax-primary"></i> 9. pembayaran</span>
                    <span class="badge bg-secondary">Kas</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities Tables -->
<div class="row g-4">
    <!-- SPT Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-medical me-2 text-primary"></i>5 SPT Terbaru</h6>
                <a href="spt.php" class="small text-tax-primary text-decoration-none fw-semibold">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Wajib Pajak</th>
                            <th>Jenis Pajak</th>
                            <th>Pajak Terutang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_spt)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data SPT</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_spt as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_wp']) ?></td>
                                <td><?= htmlspecialchars($row['nama_pajak']) ?></td>
                                <td class="fw-bold text-tax-primary"><?= format_rupiah($row['jumlah_pajak']) ?></td>
                                <td><?= render_status_badge($row['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pembayaran Terbaru -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-cash-stack me-2 text-success"></i>5 Pembayaran Terbaru</h6>
                <a href="pembayaran.php" class="small text-tax-primary text-decoration-none fw-semibold">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>No. Tagihan</th>
                            <th>Wajib Pajak</th>
                            <th>Jumlah Bayar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_bayar)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data pembayaran</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_bayar as $row): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($row['nomor_tagihan']) ?></code></td>
                                <td><?= htmlspecialchars($row['nama_wp']) ?></td>
                                <td class="fw-bold text-success"><?= format_rupiah($row['jumlah_bayar']) ?></td>
                                <td><?= render_status_badge($row['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

