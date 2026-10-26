<?php
// my_bookings.php - Dashboard & Riwayat Pemesanan Fasilitas Pengguna
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';

require_login();
$currentUser = current_user();

// Handle Pembatalan Booking oleh User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancelId = (int)$_POST['booking_id'];
    $stmtC = $db->prepare("
        UPDATE bookings
        SET booking_status = 'cancelled'
        WHERE id = ? AND user_id = ? AND booking_status IN ('pending', 'approved')
    ");
    $stmtC->execute([$cancelId, $currentUser['id']]);
    set_flash('info', 'Permohonan booking telah dibatalkan.');
    redirect_to(BASE_URL . "/my_bookings.php");
}

$pageTitle = "Booking Saya";
require_once __DIR__ . '/includes/header.php';

// Ambil data booking user
$statusTab = isset($_GET['status']) ? trim($_GET['status']) : '';
$sql = "
    SELECT b.*, f.name as facility_name, f.category as facility_category, f.building, f.image
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    WHERE b.user_id = ?
";
$params = [$currentUser['id']];

if (!empty($statusTab)) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $statusTab;
}

$sql .= " ORDER BY b.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$myBookings = $stmt->fetchAll();

// Hitung statistik user
$stmtStats = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
        SUM(CASE WHEN booking_status = 'approved' THEN 1 ELSE 0 END) as approved_cnt,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed_cnt
    FROM bookings
    WHERE user_id = ?
");
$stmtStats->execute([$currentUser['id']]);
$userStats = $stmtStats->fetch();
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Booking Saya</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">Riwayat & Status E-Booking Saya</h1>
                <p class="text-muted mb-0">Kelola reservasi fasilitas, cek konfirmasi approval, bayar e-payment, dan cetak tiket QR Code.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/booking.php" class="btn btn-primary-usu shadow">
                    <i class="bi bi-calendar-plus-fill"></i> Ajukan Booking Baru
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Stat Cards Mahasiswa -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-3 bg-light text-dark p-3 fs-4"><i class="bi bi-card-checklist"></i></div>
                <div>
                    <div class="small text-muted fw-semibold">Total Booking</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int)$userStats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning-subtle text-warning-emphasis p-3 fs-4"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="small text-muted fw-semibold">Menunggu</div>
                    <div class="fs-4 fw-bold text-warning-emphasis"><?= (int)$userStats['pending_cnt'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success-subtle text-success p-3 fs-4"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="small text-muted fw-semibold">Disetujui (Aktif)</div>
                    <div class="fs-4 fw-bold text-success"><?= (int)$userStats['approved_cnt'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 bg-white rounded-4 border shadow-sm d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4"><i class="bi bi-mortarboard"></i></div>
                <div>
                    <div class="small text-muted fw-semibold">Selesai Digelar</div>
                    <div class="fs-4 fw-bold text-primary"><?= (int)$userStats['completed_cnt'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tab Bar -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
        <a href="<?= BASE_URL ?>/my_bookings.php" class="btn btn-sm <?= empty($statusTab) ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">Semua (<?= (int)$userStats['total'] ?>)</a>
        <a href="<?= BASE_URL ?>/my_bookings.php?status=approved" class="btn btn-sm <?= $statusTab === 'approved' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">Disetujui / Aktif (<?= (int)$userStats['approved_cnt'] ?>)</a>
        <a href="<?= BASE_URL ?>/my_bookings.php?status=pending" class="btn btn-sm <?= $statusTab === 'pending' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">Menunggu Approval (<?= (int)$userStats['pending_cnt'] ?>)</a>
        <a href="<?= BASE_URL ?>/my_bookings.php?status=completed" class="btn btn-sm <?= $statusTab === 'completed' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">Selesai (<?= (int)$userStats['completed_cnt'] ?>)</a>
    </div>

    <!-- Daftar Booking -->
    <?php if (empty($myBookings)): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5">
            <i class="bi bi-calendar-x text-muted display-3 mb-3 d-block"></i>
            <h4 class="fw-bold text-dark">Belum Ada Permohonan Booking</h4>
            <p class="text-muted mb-4">Anda belum memiliki riwayat pemesanan fasilitas pada kategori ini.</p>
            <a href="<?= BASE_URL ?>/booking.php" class="btn btn-primary-usu shadow">Pesan Fasilitas Sekarang</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($myBookings as $b): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="row align-items-center g-3">
                            <div class="col-md-2 text-center text-md-start">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($b['image']) ?>" alt="<?= htmlspecialchars($b['facility_name']) ?>" class="rounded-3 img-fluid shadow-sm" style="max-height:110px; width:100%; object-fit:cover;">
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['booking_code']) ?></span>
                                    <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($b['facility_category']) ?></span>
                                    <?php if ($b['booking_status'] === 'approved'): ?>
                                        <span class="badge-booking badge-approved"><i class="bi bi-check-circle-fill"></i> Disetujui</span>
                                    <?php elseif ($b['booking_status'] === 'pending'): ?>
                                        <span class="badge-booking badge-pending"><i class="bi bi-hourglass-split"></i> Menunggu Approval</span>
                                    <?php elseif ($b['booking_status'] === 'completed'): ?>
                                        <span class="badge-booking badge-completed"><i class="bi bi-mortarboard-fill"></i> Selesai</span>
                                    <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                        <span class="badge-booking badge-cancelled">Dibatalkan</span>
                                    <?php else: ?>
                                        <span class="badge-booking badge-rejected">Ditolak</span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($b['facility_name']) ?></h5>
                                <div class="small text-muted mb-1">
                                    <i class="bi bi-calendar-event text-success me-1"></i> <?= tgl_indo($b['booking_date']) ?> 
                                    (<?= substr($b['start_time'], 0, 5) ?> - <?= substr($b['end_time'], 0, 5) ?> WIB - <?= $b['duration_hours'] ?> Jam)
                                </div>
                                <div class="small text-secondary fw-semibold">
                                    <i class="bi bi-tag text-primary me-1"></i> Acara: <?= htmlspecialchars($b['purpose']) ?>
                                </div>
                                <?php if (!empty($b['admin_notes'])): ?>
                                    <div class="alert alert-light border p-2 small mt-2 mb-0">
                                        <strong>Catatan Admin:</strong> <?= htmlspecialchars($b['admin_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 text-md-end">
                                <div class="mb-2">
                                    <div class="small text-muted">Status Pembayaran:</div>
                                    <span class="badge <?= $b['payment_status'] === 'paid' ? 'bg-success' : ($b['payment_status'] === 'free_academic' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>">
                                        <?= strtoupper($b['payment_status']) ?>
                                    </span>
                                    <div class="fw-bold text-dark fs-6 mt-1"><?= rupiah($b['total_amount']) ?></div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                    <?php if ($b['payment_status'] === 'unpaid'): ?>
                                        <a href="<?= BASE_URL ?>/checkout.php?code=<?= htmlspecialchars($b['booking_code']) ?>" class="btn btn-warning btn-sm fw-bold">
                                            <i class="bi bi-credit-card"></i> Bayar E-Payment
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($b['booking_status'] === 'approved'): ?>
                                        <a href="<?= BASE_URL ?>/ticket.php?code=<?= htmlspecialchars($b['booking_code']) ?>" class="btn btn-primary-usu btn-sm">
                                            <i class="bi bi-qr-code"></i> E-Ticket QR
                                        </a>
                                    <?php endif; ?>

                                    <?php if (in_array($b['booking_status'], ['pending', 'approved'])): ?>
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pemesanan ini?');" class="d-inline">
                                            <input type="hidden" name="action" value="cancel_booking">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-x-circle"></i> Batalkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
