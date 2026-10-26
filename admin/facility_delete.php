<?php
// admin/facility_delete.php - Hapus Fasilitas
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $db->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->execute([$id]);
    set_flash('info', 'Fasilitas telah dihapus dari sistem.');
}

redirect_to(BASE_URL . "/admin/facilities.php");
