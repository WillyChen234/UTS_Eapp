<?php
// config/database.php
// Konfigurasi Database PDO - Mendukung SQLite (Default Mandiri) & MySQL (XAMPP/Laragon)

require_once __DIR__ . '/app.php';

// Pengaturan Tipe Database: 'sqlite' atau 'mysql'
// Jika MySQL tersedia di localhost, user cukup ubah ke 'mysql'
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');

// Konfigurasi MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'usu_smartfacility');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Lokasi file SQLite
define('SQLITE_FILE', dirname(__DIR__) . '/database/data.sqlite');

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = DB_DRIVER;

    try {
        if ($driver === 'sqlite') {
            $dbDir = dirname(SQLITE_FILE);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            $dsn = "sqlite:" . SQLITE_FILE;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("PRAGMA foreign_keys = ON;");
        } else {
            // MySQL
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL fails
        if ($driver === 'mysql') {
            try {
                $dsn = "sqlite:" . SQLITE_FILE;
                $pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (Exception $ex) {
                die("Koneksi Database Gagal: " . $e->getMessage() . " / Fallback SQLite: " . $ex->getMessage());
            }
        } else {
            die("Koneksi Database SQLite Gagal: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Global DB instance
$db = get_db_connection();
