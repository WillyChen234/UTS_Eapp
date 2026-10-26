<?php
// facilities.php - Katalog Lengkap Fasilitas Kampus USU
$pageTitle = "Katalog Fasilitas";
require_once __DIR__ . '/includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$sql = "SELECT * FROM facilities WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR building LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY category ASC, name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$facilities = $stmt->fetchAll();

// Kategori unik
$categories = ['Laboratorium', 'Aula', 'Lapangan Olahraga', 'Ruang Seminar'];
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Katalog Fasilitas</li>
            </ol>
        </nav>
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <h1 class="h2 fw-bold text-dark mb-1">Katalog Fasilitas Kampus USU</h1>
                <p class="text-muted mb-0">Temukan ruangan dan sarana olahraga yang siap digunakan untuk berbagai kegiatan kampus.</p>
            </div>
            <div class="col-md-6">
                <form action="<?= BASE_URL ?>/facilities.php" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari nama fasilitas, gedung..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary-usu">Cari</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Filter Kategori Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
        <a href="<?= BASE_URL ?>/facilities.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>" 
           class="btn btn-sm <?= empty($category) ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">
            Semua Kategori
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/facilities.php?category=<?= urlencode($cat) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="btn btn-sm <?= $category === $cat ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid List Fasilitas -->
    <?php if (empty($facilities)): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5">
            <i class="bi bi-building-x text-muted display-3 mb-3 d-block"></i>
            <h4 class="fw-bold text-dark">Tidak Ada Fasilitas yang Cocok</h4>
            <p class="text-muted mb-4">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
            <a href="<?= BASE_URL ?>/facilities.php" class="btn btn-secondary-usu">Reset Filter</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($facilities as $fac): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card-facility">
                        <div class="card-facility-img-wrap">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($fac['image']) ?>" alt="<?= htmlspecialchars($fac['name']) ?>">
                            <span class="badge-category"><?= htmlspecialchars($fac['category']) ?></span>
                            <span class="badge-status-chip <?= $fac['status'] == 'available' ? 'badge-status-available' : 'badge-status-maintenance' ?>">
                                <?= $fac['status'] == 'available' ? '✓ Tersedia' : 'Dalam Perbaikan' ?>
                            </span>
                        </div>
                        <div class="card-facility-body">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <span class="badge bg-light text-secondary border small">Kode: <?= htmlspecialchars($fac['code']) ?></span>
                                <div>
                                    <span class="facility-rate-tag"><?= rupiah($fac['hourly_rate']) ?></span>
                                    <?php if ($fac['hourly_rate'] > 0): ?>
                                        <span class="facility-rate-unit">/ jam</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">
                                <a href="<?= BASE_URL ?>/facility_detail.php?id=<?= $fac['id'] ?>" class="text-dark">
                                    <?= htmlspecialchars($fac['name']) ?>
                                </a>
                            </h5>
                            <p class="small text-muted mb-2">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($fac['building']) ?>
                            </p>
                            <p class="small text-muted mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($fac['description']) ?>
                            </p>
                            
                            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                                <span class="small fw-semibold text-secondary">
                                    <i class="bi bi-people-fill me-1 text-primary"></i> <?= $fac['capacity'] ?> Orang
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>/facility_detail.php?id=<?= $fac['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                        Detail
                                    </a>
                                    <?php if ($fac['status'] === 'available'): ?>
                                        <a href="<?= BASE_URL ?>/booking.php?facility_id=<?= $fac['id'] ?>" class="btn btn-primary-usu btn-sm">
                                            Booking
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>Maintenance</button>
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
