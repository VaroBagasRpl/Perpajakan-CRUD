<?php
// ========================================================
// Helper Functions untuk Sistem Informasi Perpajakan
// ========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format angka ke mata uang Rupiah
 */
function format_rupiah($angka) {
    if ($angka === null || $angka === '') return 'Rp 0';
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal format Indonesia (contoh: 15 Januari 2026)
 */
function format_tanggal($tanggal, $include_time = false) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulan_indo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    if (!$ts) return '-';
    $tgl = date('j', $ts);
    $bln = $bulan_indo[(int)date('n', $ts)];
    $thn = date('Y', $ts);
    $res = "$tgl $bln $thn";
    if ($include_time) {
        $res .= ' ' . date('H:i', $ts);
    }
    return $res;
}

/**
 * Sanitasi string input
 */
function sanitize($str) {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash alert message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Render Bootstrap badge berdasarkan status
 */
function render_status_badge($status) {
    $map = [
        'Aktif'         => 'bg-success',
        'Nonaktif'      => 'bg-secondary',
        'Draft'         => 'bg-secondary',
        'Diajukan'      => 'bg-primary',
        'Diverifikasi'  => 'bg-info text-dark',
        'Ditolak'       => 'bg-danger',
        'Belum Lunas'   => 'bg-warning text-dark',
        'Sebagian'      => 'bg-info text-dark',
        'Lunas'         => 'bg-success',
        'Berhasil'      => 'bg-success',
        'Menunggu'      => 'bg-warning text-dark',
        'Gagal'         => 'bg-danger',
    ];
    $cls = isset($map[$status]) ? $map[$status] : 'bg-secondary';
    return '<span class="badge ' . $cls . ' px-2 py-1 rounded-pill">' . htmlspecialchars($status) . '</span>';
}

