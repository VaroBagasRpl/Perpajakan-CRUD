<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$success = false;
$error = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    try {
        // Koneksi ke MySQL tanpa memilih database dulu
        $pdo = getDB(false);
        if (!$pdo) {
            throw new Exception("Tidak dapat terhubung ke MySQL di " . DB_HOST . ":" . DB_PORT . ". Pastikan MySQL di Laragon sudah aktif (klik 'Start All' di Laragon).");
        }

        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("File database.sql tidak ditemukan!");
        }

        $sql = file_get_contents($sqlFile);
        
        // Eksekusi SQL dump
        $pdo->exec($sql);

        $success = true;
        $message = "Database 'perpajakan' dan seluruh 9 tabel berhasil dibuat beserta data sampel!";
        set_flash_message('success', $message);
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$dbReady = isDatabaseReady();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Database - Sistem Informasi Perpajakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="bg-tax-primary p-4 text-white text-center">
                    <i class="bi bi-database-fill-gear display-4 mb-2"></i>
                    <h3 class="fw-bold mb-1">Inisialisasi Database Perpajakan</h3>
                    <p class="mb-0 opacity-75 small">Sistem Informasi Perpajakan Terpadu</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">Gagal Menginisialisasi</h6>
                                <p class="mb-0 small"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($dbReady): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">Database Sudah Terpasang!</h6>
                                <p class="mb-0 small">Database <strong>perpajakan</strong> dan 9 tabel telah siap digunakan.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-info-circle-fill flex-shrink-0 me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">Database Belum Terpasang</h6>
                                <p class="mb-0 small">Klik tombol di bawah untuk membuat database dan 9 tabel perpajakan secara otomatis.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="border rounded-3 p-3 bg-white mb-4">
                        <h6 class="fw-bold text-tax-primary mb-2"><i class="bi bi-table me-2"></i>9 Tabel Sesuai Dokumen Struktur:</h6>
                        <ol class="small text-muted mb-0 ps-3">
                            <li><strong>wajib_pajak</strong> (Identitas WP, NPWP, NIK, Status)</li>
                            <li><strong>petugas</strong> (Administrator & Pengelola Sistem)</li>
                            <li><strong>jenis_pajak</strong> (PPh 21, PPh 23, Final UMKM, PPN, PBB-P2)</li>
                            <li><strong>tarif_pajak</strong> (Persentase tarif & periode berlaku)</li>
                            <li><strong>objek_pajak</strong> (Kewajiban objek pajak & nilai objek)</li>
                            <li><strong>masa_pajak</strong> (Tahun, Bulan, & Periode Pelaporan)</li>
                            <li><strong>spt</strong> (Surat Pemberitahuan & Status Verifikasi)</li>
                            <li><strong>tagihan</strong> (Nomor Billing & Status Pembayaran)</li>
                            <li><strong>pembayaran</strong> (Transaksi, Nominal, & Metode Bayar)</li>
                        </ol>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="install">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-tax-primary btn-lg fw-semibold shadow-sm">
                                <i class="bi bi-lightning-charge-fill me-2"></i>
                                <?= $dbReady ? 'Pasang Ulang / Reset Database' : 'Inisialisasi Database Sekarang' ?>
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda Landing Page
                            </a>
                            <?php if ($dbReady): ?>
                            <a href="dashboard.php" class="btn btn-success">
                                <i class="bi bi-speedometer2 me-1"></i> Buka Dashboard CRUD
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="accordion" id="manualGuide">
                        <div class="accordion-item border-0 bg-light rounded-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent py-2 small fw-semibold text-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseManual">
                                    <i class="bi bi-info-circle me-2"></i> Mau import manual lewat phpMyAdmin?
                                </button>
                            </h2>
                            <div id="collapseManual" class="accordion-collapse collapse" data-bs-parent="#manualGuide">
                                <div class="accordion-body small text-muted pt-0">
                                    <ol class="ps-3 mb-0">
                                        <li>Buka Laragon lalu klik tombol <strong>Database</strong> atau buka <code>http://localhost/phpmyadmin</code></li>
                                        <li>Pilih tab <strong>Import</strong></li>
                                        <li>Pilih file <code>database.sql</code> yang ada di dalam folder <code>c:\laragon\www\Perpajakan\database.sql</code></li>
                                        <li>Klik tombol <strong>Kirim / Go / Import</strong> di bagian bawah</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

