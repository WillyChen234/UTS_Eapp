<?php
// api/calendar_events.php
// Mengembalikan data JSON event jadwal pemakaian fasilitas untuk FullCalendar.js

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'approved';

try {
    $sql = "
        SELECT 
            b.id,
            b.booking_code,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.purpose,
            b.organization_type,
            b.booking_status,
            f.id as facility_id,
            f.name as facility_name,
            f.category as facility_category,
            f.building,
            u.name as borrower_name
        FROM bookings b
        JOIN facilities f ON b.facility_id = f.id
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_status IN ('approved', 'completed')
    ";

    $params = [];
    if ($facilityId > 0) {
        $sql .= " AND b.facility_id = ?";
        $params[] = $facilityId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];

    // Warna berdasarkan kategori fasilitas
    $categoryColors = [
        'Laboratorium' => ['bg' => '#057a55', 'border' => '#046c4e'],
        'Aula' => ['bg' => '#d97706', 'border' => '#b45309'],
        'Lapangan Olahraga' => ['bg' => '#1d4ed8', 'border' => '#1e40af'],
        'Ruang Seminar' => ['bg' => '#7c3aed', 'border' => '#6d28d9']
    ];

    foreach ($bookings as $b) {
        $color = $categoryColors[$b['facility_category']] ?? ['bg' => '#057a55', 'border' => '#046c4e'];
        
        $startIso = $b['booking_date'] . 'T' . substr($b['start_time'], 0, 5) . ':00';
        $endIso = $b['booking_date'] . 'T' . substr($b['end_time'], 0, 5) . ':00';

        $events[] = [
            'id' => $b['id'],
            'title' => $b['facility_name'] . ' - ' . $b['purpose'],
            'start' => $startIso,
            'end' => $endIso,
            'backgroundColor' => $color['bg'],
            'borderColor' => $color['border'],
            'textColor' => '#ffffff',
            'extendedProps' => [
                'booking_code' => $b['booking_code'],
                'facility_name' => $b['facility_name'],
                'facility_category' => $b['facility_category'],
                'building' => $b['building'],
                'borrower' => $b['borrower_name'],
                'organization' => $b['organization_type'],
                'time_formatted' => substr($b['start_time'], 0, 5) . ' - ' . substr($b['end_time'], 0, 5) . ' WIB',
                'purpose' => $b['purpose'],
                'status' => $b['booking_status']
            ]
        ];
    }

    echo json_encode($events);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
