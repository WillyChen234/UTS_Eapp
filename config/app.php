<?php
// config/app.php
// Konfigurasi Utama Sistem E-Booking & E-Payment Fasilitas Kampus USU

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aktifkan Output Buffering agar header redirection tidak bentrok dengan output HTML
if (!ob_get_level()) {
    ob_start();
}

// Helper redirection yang aman dari error "headers already sent"
function redirect_to($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    }
    echo '<script type="text/javascript">window.location.href="' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '" /></noscript>';
    exit;
}

// Zona Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'USU SmartFacility');
define('APP_TITLE', 'E-Booking & E-Payment Fasilitas Kampus');
define('APP_CAMPUS', 'Fakultas Vokasi - D3 Teknik Informatika USU');
define('APP_VERSION', '2.0.0');

// Base URL helper
$serverPort = $_SERVER['SERVER_PORT'] ?? 80;
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $serverPort == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = str_replace('\\', '/', dirname($scriptName));
if ($dir === '/' || $dir === '.') {
    $dir = '';
}
$baseUrl = rtrim($protocol . $host . $dir, '/');
// If script is in /admin or /api, adjust base URL
if (strpos($baseUrl, '/admin') !== false) {
    $baseUrl = substr($baseUrl, 0, strrpos($baseUrl, '/admin'));
}
if (strpos($baseUrl, '/api') !== false) {
    $baseUrl = substr($baseUrl, 0, strrpos($baseUrl, '/api'));
}
define('BASE_URL', $baseUrl);

// Helper flash messages
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Format Rupiah
function rupiah($nominal) {
    if ($nominal == 0) {
        return 'Gratis (Akademik)';
    }
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Format Tanggal Indonesia
function tgl_indo($tanggal) {
    if (!$tanggal) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecahkan = explode('-', substr($tanggal, 0, 10));
    if (count($pecahkan) < 3) return $tanggal;
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
