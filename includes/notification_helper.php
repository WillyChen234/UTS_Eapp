<?php
// includes/notification_helper.php
// Helper Simulasi Notifikasi Transaksi (WhatsApp & Email Otomatis)

require_once __DIR__ . '/../config/database.php';

function send_simulated_notification($userId, $bookingId, $channel, $title, $message, $recipient) {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, booking_id, channel, title, message, recipient, is_read, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, datetime('now', 'localtime'))
        ");
        $stmt->execute([$userId, $bookingId, $channel, $title, $message, $recipient]);
        return true;
    } catch (Exception $e) {
        // Fallback for MySQL NOW()
        try {
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, booking_id, channel, title, message, recipient, is_read, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$userId, $bookingId, $channel, $title, $message, $recipient]);
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }
}

function notify_booking_created($booking, $user, $facility) {
    // 1. WhatsApp notification
    $waMsg = "🏛 *USU SmartFacility - Permohonan Booking Diterima*\n\n"
           . "Halo *" . $user['name'] . "*,\n"
           . "Permohonan booking fasilitas Anda berhasil dibuat dengan detail:\n"
           . "• Kode Booking: *" . $booking['booking_code'] . "*\n"
           . "• Fasilitas: *" . $facility['name'] . "*\n"
           . "• Tanggal: " . tgl_indo($booking['booking_date']) . "\n"
           . "• Pukul: " . substr($booking['start_time'], 0, 5) . " - " . substr($booking['end_time'], 0, 5) . " WIB\n"
           . "• Total Biaya: *" . rupiah($booking['total_amount']) . "*\n\n"
           . "Status: *MENUNGGU PEMBAYARAN / APPROVAL*\n"
           . "Silakan cek riwayat booking Anda di portal: " . BASE_URL . "/my_bookings.php";

    send_simulated_notification($user['id'], $booking['id'], 'whatsapp', 'Pemesanan Fasilitas Dibuat', $waMsg, $user['phone']);

    // 2. Email notification
    $emailMsg = "Permohonan booking fasilitas " . $facility['name'] . " dengan kode " . $booking['booking_code'] . " telah terdaftar di sistem USU SmartFacility. Total tagihan: " . rupiah($booking['total_amount']) . ".";
    send_simulated_notification($user['id'], $booking['id'], 'email', 'Konfirmasi Permohonan Booking USU', $emailMsg, $user['email']);
}

function notify_booking_status_updated($booking, $user, $facility, $newStatus, $adminNotes = '') {
    $statusText = [
        'approved' => 'DISETUJUI (APPROVED) ✅',
        'rejected' => 'DITOLAK (REJECTED) ❌',
        'completed' => 'SELESAI (COMPLETED) 🎓',
        'cancelled' => 'DIBATALKAN ⚠️'
    ][$newStatus] ?? strtoupper($newStatus);

    $waMsg = "🏛 *USU SmartFacility - Pembaruan Status Booking*\n\n"
           . "Halo *" . $user['name'] . "*,\n"
           . "Permohonan booking *" . $booking['booking_code'] . "* untuk *" . $facility['name'] . "* telah *" . $statusText . "* oleh Admin Sarpras Vokasi USU.\n\n";

    if (!empty($adminNotes)) {
        $waMsg .= "📌 *Catatan Admin:* " . $adminNotes . "\n\n";
    }

    if ($newStatus === 'approved') {
        $waMsg .= "🎟 *E-Ticket & QR Code resmi Anda telah aktif!*\n"
                . "Akses tiket masuk Anda melalui link berikut:\n"
                . BASE_URL . "/ticket.php?code=" . $booking['booking_code'] . "\n\n"
                . "Tunjukkan QR Code tersebut kepada petugas saat memasuki lokasi.";
    }

    send_simulated_notification($user['id'], $booking['id'], 'whatsapp', 'Status Booking: ' . $statusText, $waMsg, $user['phone']);
    send_simulated_notification($user['id'], $booking['id'], 'email', 'Status Booking: ' . $statusText, $waMsg, $user['email']);
}
