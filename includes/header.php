<?php
// includes/header.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$currentUser = current_user();
$activePage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?><?= APP_NAME ?> - <?= APP_CAMPUS ?></title>
    <meta name="description" content="Sistem Informasi E-Booking dan E-Payment Fasilitas Kampus Terintegrasi Fakultas Vokasi D3 Teknik Informatika Universitas Sumatera Utara (USU)">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
</head>
<body>

    <!-- Top Campus Brand Bar -->
    <div class="top-brand-bar d-none d-md-block">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-geo-alt-fill text-warning me-1"></i> Kampus Padang Bulan, Medan 20155</span>
                <span>•</span>
                <span><i class="bi bi-mortarboard-fill text-warning me-1"></i> D3 Teknik Informatika - Fakultas Vokasi USU</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span><i class="bi bi-clock-fill text-warning me-1"></i> Layanan Sarpras: 08.00 - 17.00 WIB</span>
                <?php if ($currentUser): ?>
                    <span class="badge bg-light text-dark fw-semibold">
                        <i class="bi bi-person-circle text-success me-1"></i> <?= htmlspecialchars($currentUser['name']) ?> (<?= strtoupper($currentUser['role']) ?>)
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
                <div class="navbar-brand-content">
                    <div class="brand-logo-badge">
                        <i class="bi bi-building-check"></i>
                    </div>
                    <div>
                        <div class="brand-text-title"><?= APP_NAME ?></div>
                        <div class="brand-text-sub">Fasilitas Kampus Vokasi USU</div>
                    </div>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link-custom <?= $activePage == 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                            <i class="bi bi-house"></i> Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-custom <?= $activePage == 'facilities.php' || $activePage == 'facility_detail.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/facilities.php">
                            <i class="bi bi-grid-3x3-gap"></i> Fasilitas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-custom <?= $activePage == 'calendar.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/calendar.php">
                            <i class="bi bi-calendar3"></i> Kalender Jadwal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link-custom <?= $activePage == 'verify.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/verify.php">
                            <i class="bi bi-qr-code-scan"></i> Verifikasi Tiket
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-gold-usu btn-sm">
                                <i class="bi bi-speedometer2"></i> Dashboard Admin
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/my_bookings.php" class="btn btn-secondary-usu btn-sm <?= $activePage == 'my_bookings.php' ? 'border-success text-success' : '' ?>">
                                <i class="bi bi-ticket-perforated"></i> Booking Saya
                            </a>
                        <?php endif; ?>

                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="badge bg-success rounded-circle p-1"><i class="bi bi-person text-white"></i></span>
                                <span class="d-none d-sm-inline fw-semibold text-truncate" style="max-width:120px;"><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($currentUser['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($currentUser['email']) ?></div>
                                    <span class="badge bg-primary-subtle text-primary mt-1"><?= strtoupper($currentUser['role']) ?></span>
                                </li>
                                <?php if ($currentUser['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/bookings.php"><i class="bi bi-check2-circle me-2 text-primary"></i> Kelola Booking</a></li>
                                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/facilities.php"><i class="bi bi-building me-2 text-primary"></i> Kelola Fasilitas</a></li>
                                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/reports.php"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i> Laporan Penggunaan</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/my_bookings.php"><i class="bi bi-card-checklist me-2 text-success"></i> Riwayat Pemesanan</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary-usu btn-sm">
                            <i class="bi bi-box-arrow-in-right"></i> Masuk
                        </a>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary-usu btn-sm">
                            <i class="bi bi-person-plus"></i> Daftar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Flash Notification -->
    <?php $flash = get_flash(); if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="bi <?= $flash['type'] == 'success' ? 'bi-check-circle-fill' : ($flash['type'] == 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> fs-5 me-2"></i>
                <div><?= $flash['message'] ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
