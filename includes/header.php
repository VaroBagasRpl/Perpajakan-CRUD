<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? 'Sistem Informasi Perpajakan';
$active_nav = $active_nav ?? 'dashboard';
$dbReady = isDatabaseReady();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SIP Perpajakan</title>
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

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-tax sticky-top py-2 px-3">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none me-2" type="button" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('show')">
            <i class="bi bi-list fs-5"></i>
        </button>

        <a class="navbar-brand navbar-brand-tax me-4" href="dashboard.php">
            <div class="bg-tax-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="bi bi-bank2"></i>
            </div>
            <div>
                <span class="d-block fw-bold fs-6 lh-1">SIP PERPAJAKAN</span>
                <small class="text-muted fw-normal" style="font-size: 0.7rem;">Sistem Informasi Manajemen Perpajakan</small>
            </div>
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="index.php" class="btn btn-sm btn-outline-success d-none d-sm-inline-flex align-items-center gap-1">
                <i class="bi bi-globe"></i> Landing Page
            </a>
            <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1">
                <i class="bi bi-database"></i> phpMyAdmin
            </a>
            <?php if (!$dbReady): ?>
                <a href="setup.php" class="btn btn-sm btn-warning text-dark fw-bold pulse">
                    <i class="bi bi-exclamation-triangle-fill"></i> Setup DB
                </a>
            <?php else: ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small d-none d-sm-inline-block">
                    <i class="bi bi-check2-circle"></i> MySQL Terhubung
                </span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar-wrapper">
        <div class="sidebar-heading">Menu Utama</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard Ringkasan
            </a>
        </nav>

        <div class="sidebar-heading mt-2">Master Wajib Pajak & Petugas</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link <?= $active_nav === 'wajib_pajak' ? 'active' : '' ?>" href="wajib_pajak.php">
                <i class="bi bi-people-fill"></i> 1. Wajib Pajak
            </a>
            <a class="nav-link <?= $active_nav === 'petugas' ? 'active' : '' ?>" href="petugas.php">
                <i class="bi bi-person-badge-fill"></i> 2. Petugas Pajak
            </a>
        </nav>

        <div class="sidebar-heading mt-2">Parameter & Objek Pajak</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link <?= $active_nav === 'jenis_pajak' ? 'active' : '' ?>" href="jenis_pajak.php">
                <i class="bi bi-tags-fill"></i> 3. Jenis Pajak
            </a>
            <a class="nav-link <?= $active_nav === 'tarif_pajak' ? 'active' : '' ?>" href="tarif_pajak.php">
                <i class="bi bi-percent"></i> 4. Tarif Pajak
            </a>
            <a class="nav-link <?= $active_nav === 'objek_pajak' ? 'active' : '' ?>" href="objek_pajak.php">
                <i class="bi bi-building-check"></i> 5. Objek Pajak
            </a>
            <a class="nav-link <?= $active_nav === 'masa_pajak' ? 'active' : '' ?>" href="masa_pajak.php">
                <i class="bi bi-calendar3"></i> 6. Masa Pajak
            </a>
        </nav>

        <div class="sidebar-heading mt-2">Transaksi & Pelaporan</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link <?= $active_nav === 'spt' ? 'active' : '' ?>" href="spt.php">
                <i class="bi bi-file-earmark-text-fill"></i> 7. SPT (Surat Pemberitahuan)
            </a>
            <a class="nav-link <?= $active_nav === 'tagihan' ? 'active' : '' ?>" href="tagihan.php">
                <i class="bi bi-receipt-cutoff"></i> 8. Tagihan Pajak
            </a>
            <a class="nav-link <?= $active_nav === 'pembayaran' ? 'active' : '' ?>" href="pembayaran.php">
                <i class="bi bi-credit-card-2-front-fill"></i> 9. Pembayaran Pajak
            </a>
        </nav>

        <div class="mt-auto p-3 border-top bg-light">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <small class="text-muted fw-bold">DATABASE</small>
                <a href="setup.php" class="text-tax-primary small text-decoration-none" title="Reset / Install Ulang"><i class="bi bi-arrow-repeat"></i> Setup</a>
            </div>
            <div class="small text-muted mb-2">
                <span class="d-block text-truncate">Database: <code>perpajakan</code></span>
                <span class="d-block text-truncate">User: <code>root@localhost</code></span>
            </div>
            <a href="setup.php" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-gear-fill me-1"></i> Kelola Database
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <?php
        $flash = get_flash_message();
        if ($flash):
        ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i>
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!$dbReady && basename($_SERVER['PHP_SELF']) !== 'setup.php'): ?>
        <div class="alert alert-warning shadow-sm border-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Database Belum Diinisialisasi</h6>
                <p class="mb-0 small text-muted">Tabel perpajakan belum ditemukan di MySQL. Silakan inisialisasi database terlebih dahulu.</p>
            </div>
            <a href="setup.php" class="btn btn-tax-primary btn-sm fw-bold">
                <i class="bi bi-lightning-charge me-1"></i> Inisialisasi Database Otomatis
            </a>
        </div>
        <?php endif; ?>

