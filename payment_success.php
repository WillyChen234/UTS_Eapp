<?php
// payment_success.php - Halaman Konfirmasi Pembayaran Berhasil
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';

require_login();
$currentUser = current_user();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$stmt = $db->prepare("
    SELECT b.*, f.name as facility_name, f.building
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect_to(BASE_URL . "/my_bookings.php");
}

$pageTitle = "Pembayaran Berhasil";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="max-w-700 mx-auto" style="max-width: 650px;">
        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 bg-white text-center">
            <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:84px; height:84px; font-size:2.8rem;">
                <i class="bi bi-check-lg"></i>
            </div>
            
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill mb-2">TRANSAKSI ELEKTRONIK BERHASIL</span>
            <h2 class="fw-bold text-dark mb-1">Pembayaran Terverifikasi!</h2>
            <p class="text-muted small mb-4">
                Terima kasih, pembayaran retribusi sewa fasilitas kampus Anda telah berhasil diproses oleh sistem gateway USU SmartFacility.
            </p>

            <div class="p-4 bg-light rounded-4 border text-start mb-4">
                <div class="d-flex justify-content-between py-2 border-bottom small">
                    <span class="text-muted">Nomor Transaksi / Ref:</span>
                    <span class="fw-bold font-monospace text-dark"><?= htmlspecialchars($booking['payment_ref'] ?: 'REF-USU-' . time()) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom small">
                    <span class="text-muted">Kode Booking:</span>
                    <span class="fw-bold text-success font-monospace"><?= htmlspecialchars($booking['booking_code']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom small">
                    <span class="text-muted">Fasilitas:</span>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($booking['facility_name']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom small">
                    <span class="text-muted">Waktu Penggunaan:</span>
                    <span class="fw-bold text-dark"><?= tgl_indo($booking['booking_date']) ?> (<?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?> WIB)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom small">
                    <span class="text-muted">Metode Pembayaran:</span>
                    <span class="fw-bold text-uppercase text-primary"><?= htmlspecialchars($booking['payment_method']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 pt-3 small">
                    <span class="fw-bold text-dark fs-6">Total Dibayar:</span>
                    <span class="fw-black text-success fs-5"><?= rupiah($booking['total_amount']) ?></span>
                </div>
            </div>

            <!-- WhatsApp Notification Card Preview -->
            <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 text-start small mb-4 d-flex gap-3 align-items-center">
                <i class="bi bi-whatsapp text-success fs-2"></i>
                <div>
                    <div class="fw-bold text-success">Notifikasi WhatsApp Dikirim Otomatis</div>
                    <div class="text-muted" style="font-size:0.75rem;">
                        Konfirmasi transaksi dan e-ticket telah diteruskan ke nomor WhatsApp <strong><?= htmlspecialchars($currentUser['phone']) ?></strong>.
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <a href="<?= BASE_URL ?>/ticket.php?code=<?= htmlspecialchars($booking['booking_code']) ?>" class="btn btn-primary-usu btn-lg shadow">
                    <i class="bi bi-qr-code-scan"></i> Buka E-Ticket & QR Code
                </a>
                <a href="<?= BASE_URL ?>/my_bookings.php" class="btn btn-secondary-usu btn-lg">
                    <i class="bi bi-card-checklist"></i> Riwayat Booking Saya
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
