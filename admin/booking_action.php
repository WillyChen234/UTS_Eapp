<?php
// admin/booking_action.php - Proses Approval, Reject, dan Status Booking oleh Admin
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/notification_helper.php';

require_admin();

$action = $_REQUEST['action'] ?? '';
$bookingId = (int)($_REQUEST['id'] ?? ($_REQUEST['booking_id'] ?? 0));
$adminNotes = trim($_REQUEST['admin_notes'] ?? '');

if (!$bookingId || !in_array($action, ['approve', 'reject', 'complete'])) {
    set_flash('danger', 'Aksi tidak valid.');
    redirect_to(BASE_URL . "/admin/bookings.php");
}

// Ambil detail booking, user, dan fasilitas
$stmt = $db->prepare("
    SELECT b.*, f.name as facility_name, u.name as user_name, u.email as user_email, u.phone as user_phone
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Data booking tidak ditemukan.');
    redirect_to(BASE_URL . "/admin/bookings.php");
}

if ($action === 'approve') {
    $notes = !empty($adminNotes) ? $adminNotes : 'Permohonan disetujui oleh Kepala Sarpras Vokasi USU. Harap menjaga kebersihan dan ketertiban fasilitas.';
    $stmtUp = $db->prepare("UPDATE bookings SET booking_status = 'approved', admin_notes = ? WHERE id = ?");
    $stmtUp->execute([$notes, $bookingId]);

    // Kirim notifikasi WhatsApp & Email
    $userRecord = ['id' => $booking['user_id'], 'name' => $booking['user_name'], 'email' => $booking['user_email'], 'phone' => $booking['user_phone']];
    $facRecord = ['name' => $booking['facility_name']];
    notify_booking_status_updated($booking, $userRecord, $facRecord, 'approved', $notes);

    set_flash('success', "Permohonan {$booking['booking_code']} berhasil DISETUJUI! Notifikasi E-Ticket QR Code telah dikirimkan ke pemohon.");
} elseif ($action === 'reject') {
    $notes = !empty($adminNotes) ? $adminNotes : 'Permohonan tidak dapat disetujui karena bentrok jadwal atau kelengkapan administrasi.';
    $stmtUp = $db->prepare("UPDATE bookings SET booking_status = 'rejected', admin_notes = ? WHERE id = ?");
    $stmtUp->execute([$notes, $bookingId]);

    $userRecord = ['id' => $booking['user_id'], 'name' => $booking['user_name'], 'email' => $booking['user_email'], 'phone' => $booking['user_phone']];
    $facRecord = ['name' => $booking['facility_name']];
    notify_booking_status_updated($booking, $userRecord, $facRecord, 'rejected', $notes);

    set_flash('warning', "Permohonan {$booking['booking_code']} telah DITOLAK dengan catatan: {$notes}");
} elseif ($action === 'complete') {
    $stmtUp = $db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE id = ?");
    $stmtUp->execute([$bookingId]);

    set_flash('info', "Jadwal pemakaian {$booking['booking_code']} telah ditandai SELESAI.");
}

$referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/bookings.php');
redirect_to($referer);
