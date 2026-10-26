<?php
// api/check_slot.php
// Validasi Anti-Bentrok Jadwal Pemakaian Fasilitas Kampus

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$startTime = isset($_GET['start_time']) ? trim($_GET['start_time']) : '';
$endTime = isset($_GET['end_time']) ? trim($_GET['end_time']) : '';
$excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : 0;

if (!$facilityId || !$date || !$startTime || !$endTime) {
    echo json_encode([
        'available' => false,
        'message' => 'Parameter tidak lengkap (Fasilitas, Tanggal, Jam Mulai, dan Selesai wajib diisi).'
    ]);
    exit;
}

if ($startTime >= $endTime) {
    echo json_encode([
        'available' => false,
        'message' => 'Jam selesai harus lebih besar dari jam mulai.'
    ]);
    exit;
}

try {
    // Cek status fasilitas
    $stmtFac = $db->prepare("SELECT name, status FROM facilities WHERE id = ?");
    $stmtFac->execute([$facilityId]);
    $fac = $stmtFac->fetch();

    if (!$fac) {
        echo json_encode(['available' => false, 'message' => 'Fasilitas tidak ditemukan.']);
        exit;
    }

    if ($fac['status'] === 'maintenance') {
        echo json_encode([
            'available' => false,
            'message' => 'Fasilitas saat ini sedang dalam masa pemeliharaan (Maintenance).'
        ]);
        exit;
    }

    // Deteksi Bentrok Jadwal (S1 < E2 AND E1 > S2)
    $sql = "
        SELECT booking_code, start_time, end_time, purpose, booking_status
        FROM bookings
        WHERE facility_id = ?
          AND booking_date = ?
          AND booking_status IN ('approved', 'pending')
          AND id != ?
          AND (? < end_time AND ? > start_time)
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$facilityId, $date, $excludeId, $startTime, $endTime]);
    $conflict = $stmt->fetch();

    if ($conflict) {
        $statusLabel = $conflict['booking_status'] === 'approved' ? 'Telah Disetujui' : 'Menunggu Approval';
        echo json_encode([
            'available' => false,
            'message' => "Jadwal bentrok dengan kegiatan '{$conflict['purpose']}' (" . substr($conflict['start_time'], 0, 5) . " - " . substr($conflict['end_time'], 0, 5) . " WIB - {$statusLabel}). Silakan pilih slot jam lain.",
            'conflict' => $conflict
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Slot waktu tersedia! Anda dapat melanjutkan ke proses booking.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['available' => false, 'message' => 'Error server: ' . $e->getMessage()]);
}
