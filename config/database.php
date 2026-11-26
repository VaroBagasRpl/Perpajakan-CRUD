<?php
// ========================================================
// Konfigurasi Database MySQL (Laragon Default)
// ========================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'perpajakan');

/**
 * Mendapatkan koneksi PDO ke database
 * @param bool $selectDb Jika true, pilih database perpajakan. Jika false, hanya konek ke server MySQL.
 * @return PDO|null
 */
function getDB($selectDb = true) {
    static $pdo = null;
    if ($pdo !== null && $selectDb) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    if ($selectDb) {
        $dsn .= ";dbname=" . DB_NAME;
    }

    try {
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if ($selectDb) {
            $pdo = $conn;
        }
        return $conn;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Cek apakah database dan tabel perpajakan sudah siap
 */
function isDatabaseReady() {
    $db = getDB(true);
    if (!$db) return false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'wajib_pajak'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

