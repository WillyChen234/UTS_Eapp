<?php
// includes/auth_guard.php
// Middleware Pengamanan Akses Berdasarkan Role (Multi-Role Authentication)

require_once __DIR__ . '/../config/app.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Pengguna',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'nim_nip' => $_SESSION['user_nim_nip'] ?? '',
        'department' => $_SESSION['user_department'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? ''
    ];
}

function require_login($redirectBack = '') {
    if (!is_logged_in()) {
        $target = BASE_URL . '/login.php';
        if (!empty($redirectBack)) {
            $target .= '?redirect=' . urlencode($redirectBack);
        }
        set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses fitur pemesanan fasilitas.');
        redirect_to($target);
    }
}

function require_admin() {
    require_login();
    $user = current_user();
    if ($user['role'] !== 'admin') {
        set_flash('danger', 'Akses ditolak! Halaman ini hanya diperuntukkan bagi Administrator Sarpras USU.');
        redirect_to(BASE_URL . '/index.php');
    }
}

function require_user() {
    require_login();
    $user = current_user();
    if ($user['role'] !== 'user') {
        // Admin can also view or redirect to admin dashboard
    }
}
