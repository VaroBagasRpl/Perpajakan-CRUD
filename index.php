<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$dbReady = isDatabaseReady();
$stats = [
    'wp' => 0,
    'spt' => 0,
    'tagihan' => 0,
    'total_bayar' => 0
];

if ($dbReady) {
    try {
        $db = getDB();
        $stats['wp'] = $db->query("SELECT COUNT(*) FROM wajib_pajak WHERE status = 'Aktif'")->fetchColumn() ?: 0;
        $stats['spt'] = $db->query("SELECT COUNT(*) FROM spt")->fetchColumn() ?: 0;
        $stats['tagihan'] = $db->query("SELECT COUNT(*) FROM tagihan WHERE status = 'Lunas'")->fetchColumn() ?: 0;
        $stats['total_bayar'] = $db->query("SELECT SUM(jumlah_bayar) FROM pembayaran WHERE status = 'Berhasil'")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        // Silently continue if tables not yet populated
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Perpajakan - Portal Layanan & CRUD Database</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>

<!-- Topbar Header -->
<header class="py-2 bg-tax-primary-dark text-white border-bottom border-success border-opacity-25 small">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-shield-check text-warning me-1"></i> Portal Resmi Informasi Perpajakan</span>
            <span class="d-none d-md-inline opacity-75">| Standar Basis Data Relasional MySQL</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="setup.php" class="text-white text-decoration-none opacity-75 hover-opacity-100">
                <i class="bi bi-database-gear me-1"></i> Status DB: <?= $dbReady ? '<span class="text-success fw-bold">Aktif</span>' : '<span class="text-warning fw-bold">Belum Pasang</span>' ?>
            </a>
            <a href="http://localhost/phpmyadmin" target="_blank" class="text-white text-decoration-none opacity-75 hover-opacity-100 d-none d-sm-inline">
                <i class="bi bi-box-arrow-up-right me-1"></i> phpMyAdmin
            </a>
        </div>
    </div>
</header>

<!-- Main Navigation -->
<nav class="navbar navbar-expand-lg navbar-tax sticky-top py-3">
    <div class="container">
        <a class="navbar-brand navbar-brand-tax" href="index.php">
            <div class="bg-tax-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px;">
                <i class="bi bi-bank2 fs-5"></i>
            </div>
            <div>
                <span class="d-block fw-bold fs-5 lh-1 text-tax-primary">SIP PERPAJAKAN</span>
                <small class="text-muted fw-normal" style="font-size: 0.75rem;">Sistem Informasi Manajemen Perpajakan Terpadu</small>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-3">
                <li class="nav-item"><a class="nav-link fw-semibold active" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="#layanan">Layanan Pajak</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="#simulasi">Simulasi Pajak</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="#struktur">Struktur Database (PDF)</a></li>
                <li class="nav-item"><a class="nav-link fw-semibold" href="#modul-crud">Modul CRUD</a></li>
                <li class="nav-item ms-lg-2">
                    <a href="dashboard.php" class="btn btn-tax-primary fw-bold px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2"></i> Masuk Dashboard CRUD
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="beranda" class="hero-tax text-white py-5 py-lg-6 position-relative">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3 d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-patch-check-fill"></i> Sistem Informasi Perpajakan Terpadu
                </span>
                <h1 class="display-5 fw-extrabold lh-tight mb-3">
                    Solusi Cerdas Pengelolaan Data Wajib Pajak, SPT & Pembayaran
                </h1>
                <p class="lead opacity-90 mb-4 fw-normal">
                    Platform website aplikasi CRUD terintegrasi dengan struktur basis data MySQL 9 tabel relasional sesuai standar dokumentasi perpajakan. Mendukung siklus penuh administrasi dari pendaftaran wajib pajak hingga rekonsiliasi pembayaran.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="dashboard.php" class="btn btn-tax-accent btn-lg px-4 py-3 rounded-3 shadow">
                        <i class="bi bi-grid-fill me-2"></i> Buka Aplikasi CRUD
                    </a>
                    <a href="#struktur" class="btn btn-outline-light btn-lg px-4 py-3 rounded-3">
                        <i class="bi bi-diagram-3-fill me-2"></i> Lihat 9 Tabel Database
                    </a>
                    <a href="setup.php" class="btn btn-outline-warning btn-lg px-3 py-3 rounded-3" title="Inisialisasi Database">
                        <i class="bi bi-database-check me-1"></i> Setup DB
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card bg-white bg-opacity-10 backdrop-blur border border-white border-opacity-20 rounded-4 p-4 shadow-lg text-white">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2 border-bottom border-white border-opacity-25 pb-2">
                        <i class="bi bi-activity text-warning"></i> Ringkasan Sistem Real-time
                    </h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-10">
                                <small class="text-white-50 d-block mb-1">Wajib Pajak Aktif</small>
                                <h3 class="fw-bold mb-0 text-warning"><?= number_format($stats['wp']) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-10">
                                <small class="text-white-50 d-block mb-1">SPT Terdata</small>
                                <h3 class="fw-bold mb-0 text-info"><?= number_format($stats['spt']) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-10">
                                <small class="text-white-50 d-block mb-1">Tagihan Lunas</small>
                                <h3 class="fw-bold mb-0 text-success"><?= number_format($stats['tagihan']) ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-dark bg-opacity-25 rounded-3 border border-white border-opacity-10">
                                <small class="text-white-50 d-block mb-1">Database Relasi</small>
                                <h3 class="fw-bold mb-0 text-white">9 Tabel</h3>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-dark bg-opacity-35 rounded-3 border border-warning border-opacity-25">
                                <small class="text-white-50 d-block mb-1">Total Penerimaan Pajak Masuk</small>
                                <h4 class="fw-bold mb-0 text-warning"><?= format_rupiah($stats['total_bayar']) ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Layanan Section -->
<section id="layanan" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-tax-primary fw-bold text-uppercase small letter-spacing-1">Layanan Utama Sistem</span>
            <h2 class="fw-bold text-dark mt-1">Siklus Pengelolaan Perpajakan Terpadu</h2>
            <p class="text-muted">Dirancang lengkap untuk memenuhi alur bisnis perpajakan modern dari hulu pendaftaran hingga hilir pelunasan.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-success-subtle text-success mb-3">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">1. Registrasi Wajib Pajak</h5>
                    <p class="text-muted small">Pencatatan NPWP, NIK, alamat domisili, email kontak, dan status keaktifan Wajib Pajak Orang Pribadi maupun Badan Usaha.</p>
                    <a href="wajib_pajak.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola Wajib Pajak <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-file-earmark-ruled-fill"></i>
                    </div>
                    <h5 class="fw-bold text-dark">2. Pelaporan SPT Masa & Tahunan</h5>
                    <p class="text-muted small">Pencatatan Surat Pemberitahuan pajak, dasar pengenaan pajak, perhitungan jumlah kewajiban pajak dengan status verifikasi berkala.</p>
                    <a href="spt.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola SPT <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h5 class="fw-bold text-dark">3. Penerbitan Tagihan (Billing)</h5>
                    <p class="text-muted small">Penerbitan kode / nomor tagihan pajak otomatis, penetapan jatuh tempo pembayaran, serta pelacakan status pelunasan.</p>
                    <a href="tagihan.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola Tagihan <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-info-subtle text-info mb-3">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <h5 class="fw-bold text-dark">4. Pembayaran & Kas Masuk</h5>
                    <p class="text-muted small">Mencatat transaksi setoran pajak via Bank Transfer, Teller, Virtual Account atau QRIS dilengkapi nomor referensi bukti setor valid.</p>
                    <a href="pembayaran.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola Pembayaran <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-danger-subtle text-danger mb-3">
                        <i class="bi bi-tags"></i>
                    </div>
                    <h5 class="fw-bold text-dark">5. Tarif & Jenis Pajak Fleksibel</h5>
                    <p class="text-muted small">Konfigurasi jenis pajak (PPh 21, PPh 23, PPN 11%, Final UMKM, PBB) dan tarif berjangka waktu sesuai regulasi yang berlaku.</p>
                    <a href="tarif_pajak.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola Tarif <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-stat">
                    <div class="stat-icon bg-secondary-subtle text-secondary mb-3">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="fw-bold text-dark">6. Objek & Masa Pajak</h5>
                    <p class="text-muted small">Pemetaan aset tanah, bangunan, omzet penjualan, atau penghasilan kena pajak yang dikaitkan ke periode kalender masa pajak.</p>
                    <a href="objek_pajak.php" class="text-tax-primary fw-bold text-decoration-none small mt-auto">Kelola Objek Pajak <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Simulasi Pajak Section -->
<section id="simulasi" class="py-5 bg-light">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-tax-primary fw-bold text-uppercase small">Alat Bantu Hitung</span>
                <h2 class="fw-bold text-dark mt-1">Kalkulator Simulasi Pajak Sederhana</h2>
                <p class="text-muted">Gunakan kalkulator ini untuk memperkirakan besaran pajak berdasarkan jenis pajak dan dasar pengenaan pajak (DPP) transaksi Anda.</p>
                
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Pilih Jenis & Tarif Pajak</label>
                        <select class="form-select" id="simJenisPajak">
                            <option value="5.00">PPh Pasal 21 - Tarif Pegawai Lapisan I (5%)</option>
                            <option value="15.00">PPh Pasal 21 - Tarif Pegawai Lapisan II (15%)</option>
                            <option value="0.50">PPh Final UMKM PP 55/2022 (0.5%)</option>
                            <option value="2.00">PPh Pasal 23 - Penyerahan Jasa (2%)</option>
                            <option value="11.00">PPN - Tarif Umum Barang/Jasa (11%)</option>
                            <option value="0.20">PBB - Tarif PBB-P2 Standar (0.2%)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary">Dasar Pengenaan Pajak (DPP / Nominal Transaksi)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold">Rp</span>
                            <input type="text" class="form-control input-currency" id="simNominal" placeholder="Contoh: 10.000.000" value="10.000.000">
                        </div>
                    </div>

                    <button type="button" class="btn btn-tax-primary w-100 fw-bold py-2" onclick="hitungSimulasiPajak()">
                        <i class="bi bi-calculator me-2"></i> Hitung Besaran Pajak
                    </button>

                    <div id="simHasil" class="mt-4 p-3 bg-success-subtle rounded-3 border border-success-subtle d-none">
                        <div class="d-flex justify-content-between mb-1 small text-muted">
                            <span>Dasar Pengenaan:</span>
                            <strong id="hasilDasar" class="text-dark">-</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-muted">
                            <span>Tarif Terpilih:</span>
                            <strong id="hasilTarif" class="text-dark">-</strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">Estimasi Pajak Terutang:</span>
                            <span id="hasilNominal" class="fs-4 fw-extrabold text-success">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-tax-primary text-white">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill text-warning"></i> Ketentuan Tarif Berlaku
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-3">
                            <h6 class="fw-bold text-warning mb-1">PPh Pasal 21 (Penghasilan Karyawan)</h6>
                            <p class="small mb-0 opacity-90">Dikenakan progresif berjenjang (5%, 15%, 25%, 30%, 35%) atas penghasilan kena pajak setelah dikurangi PTKP.</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-10 rounded-3">
                            <h6 class="fw-bold text-warning mb-1">PPh Final UMKM 0,5%</h6>
                            <p class="small mb-0 opacity-90">Bagi pelaku usaha perorangan dan badan dengan peredaran bruto tertentu di bawah Rp 4,8 Miliar setahun.</p>
                        </div>
                        <div class="p-3 bg-white bg-opacity-10 rounded-3">
                            <h6 class="fw-bold text-warning mb-1">PPN 11%</h6>
                            <p class="small mb-0 opacity-90">Pajak konsumsi atas transaksi barang kena pajak (BKP) dan jasa kena pajak (JKP) di wilayah Republik Indonesia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Struktur Database PDF Section -->
<section id="struktur" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-tax-primary fw-bold text-uppercase small">Spesifikasi Dokumen PDF</span>
            <h2 class="fw-bold text-dark mt-1">Struktur Database Sistem Informasi Perpajakan</h2>
            <p class="text-muted">Representasi lengkap 9 entitas tabel, tipe data, kunci primer (PK), kunci tamu (FK), dan keterangan relasi sesuai dokumen yang dilampirkan.</p>
        </div>

        <div class="row g-4">
            <!-- 1. Wajib Pajak -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>1. Tabel Wajib Pajak (wajib_pajak)</h6>
                        <span class="badge bg-warning text-dark">Master</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan identitas orang pribadi atau badan yang terdaftar sebagai wajib pajak.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_wajib_pajak</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>npwp</code></td><td>VARCHAR(25)</td><td><span class="badge bg-secondary">UNIQUE, NOT NULL</span></td></tr>
                                <tr><td><code>nama</code></td><td>VARCHAR(100)</td><td>NOT NULL</td></tr>
                                <tr><td><code>nik</code></td><td>VARCHAR(20)</td><td>NULL</td></tr>
                                <tr><td><code>alamat</code></td><td>TEXT</td><td>NULL</td></tr>
                                <tr><td><code>email</code></td><td>VARCHAR(100)</td><td>NULL</td></tr>
                                <tr><td><code>no_telepon</code></td><td>VARCHAR(20)</td><td>NULL</td></tr>
                                <tr><td><code>tanggal_daftar</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>status</code></td><td>ENUM('Aktif','Nonaktif')</td><td>DEFAULT 'Aktif'</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 2. Petugas -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>2. Tabel Petugas (petugas)</h6>
                        <span class="badge bg-warning text-dark">Admin</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan data administrator atau petugas yang mengelola sistem perpajakan.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_petugas</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>nama_petugas</code></td><td>VARCHAR(100)</td><td>NOT NULL</td></tr>
                                <tr><td><code>username</code></td><td>VARCHAR(50)</td><td><span class="badge bg-secondary">UNIQUE, NOT NULL</span></td></tr>
                                <tr><td><code>password</code></td><td>VARCHAR(255)</td><td>NOT NULL (disimpan hash)</td></tr>
                                <tr><td><code>jabatan</code></td><td>VARCHAR(50)</td><td>NULL</td></tr>
                                <tr><td><code>email</code></td><td>VARCHAR(100)</td><td>NULL</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 3. Jenis Pajak -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>3. Tabel Jenis Pajak (jenis_pajak)</h6>
                        <span class="badge bg-success">Parameter</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan kategori pajak yang dikelola sistem, seperti PPh, PPN, dan PBB.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_jenis_pajak</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>kode_pajak</code></td><td>VARCHAR(20)</td><td><span class="badge bg-secondary">UNIQUE</span></td></tr>
                                <tr><td><code>nama_pajak</code></td><td>VARCHAR(100)</td><td>NOT NULL</td></tr>
                                <tr><td><code>keterangan</code></td><td>TEXT</td><td>NULL</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. Tarif Pajak -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>4. Tabel Tarif Pajak (tarif_pajak)</h6>
                        <span class="badge bg-success">Parameter</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan informasi tarif berdasarkan jenis pajak dan periode berlakunya.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_tarif</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>id_jenis_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; jenis_pajak</span></td></tr>
                                <tr><td><code>tarif</code></td><td>DECIMAL(8,2)</td><td>NOT NULL</td></tr>
                                <tr><td><code>tanggal_mulai</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>tanggal_akhir</code></td><td>DATE</td><td>NULL</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 5. Objek Pajak -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>5. Tabel Objek Pajak (objek_pajak)</h6>
                        <span class="badge bg-info text-dark">Relasi</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan informasi objek yang berkaitan dengan kewajiban perpajakan wajib pajak.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_objek_pajak</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>id_wajib_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; wajib_pajak</span></td></tr>
                                <tr><td><code>id_jenis_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; jenis_pajak</span></td></tr>
                                <tr><td><code>nama_objek</code></td><td>VARCHAR(100)</td><td>NULL</td></tr>
                                <tr><td><code>alamat_objek</code></td><td>TEXT</td><td>NULL</td></tr>
                                <tr><td><code>nilai_objek</code></td><td>DECIMAL(15,2)</td><td>NULL</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 6. Masa Pajak -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>6. Tabel Masa Pajak (masa_pajak)</h6>
                        <span class="badge bg-success">Periode</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan periode atau jangka waktu yang menjadi dasar pelaporan pajak.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_masa_pajak</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>tahun_pajak</code></td><td>YEAR</td><td>NOT NULL</td></tr>
                                <tr><td><code>bulan_pajak</code></td><td>TINYINT</td><td>NULL (1-12)</td></tr>
                                <tr><td><code>tanggal_mulai</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>tanggal_akhir</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>keterangan</code></td><td>VARCHAR(100)</td><td>NULL</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 7. Tabel SPT -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>7. Tabel SPT (spt)</h6>
                        <span class="badge bg-danger">Transaksi</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Mencatat laporan Surat Pemberitahuan yang disampaikan oleh wajib pajak.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_spt</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>id_wajib_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; wajib_pajak</span></td></tr>
                                <tr><td><code>id_jenis_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; jenis_pajak</span></td></tr>
                                <tr><td><code>id_masa_pajak</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; masa_pajak</span></td></tr>
                                <tr><td><code>id_petugas</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; petugas, NULL</span></td></tr>
                                <tr><td><code>tanggal_lapor</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>dasar_pengenaan</code></td><td>DECIMAL(15,2)</td><td>NULL</td></tr>
                                <tr><td><code>jumlah_pajak</code></td><td>DECIMAL(15,2)</td><td>NULL</td></tr>
                                <tr><td><code>status</code></td><td>ENUM(...)</td><td>DEFAULT 'Draft'</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 8. Tabel Tagihan -->
            <div class="col-lg-6">
                <div class="card border rounded-4 overflow-hidden shadow-sm h-100">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>8. Tabel Tagihan (tagihan)</h6>
                        <span class="badge bg-danger">Billing</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Menyimpan kewajiban pembayaran pajak berdasarkan laporan SPT.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_tagihan</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>id_spt</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; spt, UNIQUE</span></td></tr>
                                <tr><td><code>nomor_tagihan</code></td><td>VARCHAR(50)</td><td><span class="badge bg-secondary">UNIQUE</span></td></tr>
                                <tr><td><code>tanggal_tagihan</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>tanggal_jatuh_tempo</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>total_tagihan</code></td><td>DECIMAL(15,2)</td><td>NOT NULL</td></tr>
                                <tr><td><code>status</code></td><td>ENUM(...)</td><td>DEFAULT 'Belum Lunas'</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 9. Tabel Pembayaran -->
            <div class="col-lg-12">
                <div class="card border rounded-4 overflow-hidden shadow-sm">
                    <div class="bg-tax-primary text-white p-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="bi bi-table me-2"></i>9. Tabel Pembayaran (pembayaran)</h6>
                        <span class="badge bg-success">Setoran Kas</span>
                    </div>
                    <div class="p-3 small text-muted border-bottom bg-light">
                        Mencatat transaksi pembayaran untuk tagihan pajak.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 small">
                            <thead class="table-light">
                                <tr><th>Field</th><th>Tipe Data</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>id_pembayaran</code></td><td>INT</td><td><span class="badge bg-primary">PK, AUTO_INCREMENT</span></td></tr>
                                <tr><td><code>id_tagihan</code></td><td>INT</td><td><span class="badge bg-info text-dark">FK &rarr; tagihan.id_tagihan</span></td></tr>
                                <tr><td><code>tanggal_pembayaran</code></td><td>DATE</td><td>NULL</td></tr>
                                <tr><td><code>jumlah_bayar</code></td><td>DECIMAL(15,2)</td><td>NOT NULL</td></tr>
                                <tr><td><code>metode_pembayaran</code></td><td>VARCHAR(50)</td><td>NULL (Bank Transfer, VA, QRIS)</td></tr>
                                <tr><td><code>nomor_referensi</code></td><td>VARCHAR(100)</td><td>NULL (Kode transaksi bank)</td></tr>
                                <tr><td><code>status</code></td><td>ENUM('Berhasil','Menunggu','Gagal')</td><td>DEFAULT 'Menunggu'</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Modul CRUD Access Links -->
<section id="modul-crud" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-tax-primary fw-bold text-uppercase small">Akses Cepat</span>
            <h2 class="fw-bold text-dark mt-1">Pintu Masuk 9 Modul CRUD Sistem</h2>
            <p class="text-muted">Klik pada modul yang ingin Anda kelola untuk menambah, mengubah, melihat, atau menghapus data.</p>
        </div>

        <div class="row g-3">
            <?php
            $modules = [
                ['wajib_pajak.php', 'bi-people-fill', '1. Wajib Pajak', 'Kelola data identitas, NPWP, NIK, dan status wajib pajak.', 'btn-outline-success'],
                ['petugas.php', 'bi-person-badge-fill', '2. Petugas Pajak', 'Kelola akun administrator, staf penilai & pemeriksa pajak.', 'btn-outline-primary'],
                ['jenis_pajak.php', 'bi-tags-fill', '3. Jenis Pajak', 'Kategori pajak: PPh 21, PPh 23, PPN, PBB-P2.', 'btn-outline-dark'],
                ['tarif_pajak.php', 'bi-percent', '4. Tarif Pajak', 'Atur persentase tarif & masa berlaku tanggal aktif.', 'btn-outline-dark'],
                ['objek_pajak.php', 'bi-building-check', '5. Objek Pajak', 'Data objek penghasilan, aset properti, dan peredaran bruto.', 'btn-outline-dark'],
                ['masa_pajak.php', 'bi-calendar3', '6. Masa Pajak', 'Atur tahun buku & bulan periode pelaporan pajak.', 'btn-outline-dark'],
                ['spt.php', 'bi-file-earmark-text-fill', '7. SPT Pajak', 'Proses Surat Pemberitahuan, verifikasi & dasar pengenaan.', 'btn-outline-danger'],
                ['tagihan.php', 'bi-receipt-cutoff', '8. Tagihan Pajak', 'Penerbitan nomor tagihan billing & jatuh tempo.', 'btn-outline-warning'],
                ['pembayaran.php', 'bi-credit-card-2-front-fill', '9. Pembayaran Pajak', 'Pencatatan setoran kas, validasi ref transaksi bank.', 'btn-outline-success'],
            ];
            foreach ($modules as $m):
            ?>
            <div class="col-md-4">
                <a href="<?= $m[0] ?>" class="card h-100 border-0 shadow-sm rounded-4 text-decoration-none p-3 card-stat">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success-subtle text-tax-primary fs-4">
                            <i class="bi <?= $m[1] ?>"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1"><?= $m[2] ?></h6>
                            <p class="small text-muted mb-0"><?= $m[3] ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="dashboard.php" class="btn btn-tax-primary btn-lg px-5 py-3 fw-bold rounded-pill shadow">
                <i class="bi bi-speedometer2 me-2"></i> Buka Dashboard Lengkap
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-tax-primary-dark text-white py-5 border-top border-success border-opacity-25">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="bg-tax-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-bank2"></i>
                    </div>
                    <span class="fw-bold fs-5">SIP PERPAJAKAN</span>
                </div>
                <p class="small opacity-75 mb-3">
                    Aplikasi Sistem Informasi Perpajakan berbasis PHP & MySQL relasional. Dibangun berdasarkan dokumen spesifikasi struktur database perpajakan untuk kemudahan administrasi wajib pajak, SPT, billing tagihan, dan rekonsiliasi kas pembayaran.
                </p>
                <div class="small opacity-75">
                    <i class="bi bi-folder-check text-warning me-1"></i> Direktori Laragon: <code>C:\laragon\www\Perpajakan</code>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <h6 class="fw-bold text-warning mb-3">Pintasan Cepat</h6>
                <ul class="list-unstyled small opacity-75 d-flex flex-column gap-2 mb-0">
                    <li><a href="dashboard.php" class="text-white text-decoration-none">Dashboard Admin</a></li>
                    <li><a href="wajib_pajak.php" class="text-white text-decoration-none">Data Wajib Pajak</a></li>
                    <li><a href="spt.php" class="text-white text-decoration-none">Pelaporan SPT</a></li>
                    <li><a href="tagihan.php" class="text-white text-decoration-none">Tagihan & Billing</a></li>
                    <li><a href="pembayaran.php" class="text-white text-decoration-none">Rekap Pembayaran</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-6">
                <h6 class="fw-bold text-warning mb-3">Petunjuk Laragon</h6>
                <p class="small opacity-75 mb-2">
                    Jalankan Laragon lalu klik <strong>Start All</strong>. Akses aplikasi melalui URL:
                </p>
                <code class="d-block p-2 bg-dark rounded small text-warning mb-3">http://localhost/Perpajakan</code>
                <a href="setup.php" class="btn btn-outline-warning btn-sm w-100">
                    <i class="bi bi-gear-fill me-1"></i> Setup / Reset Database
                </a>
            </div>
        </div>

        <hr class="border-secondary my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center small opacity-75">
            <span>&copy; <?= date('Y') ?> Sistem Informasi Perpajakan. All rights reserved.</span>
            <span>PHP Native 8.x & MySQL 5.7/8.x &bull; Laragon Ready</span>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>

