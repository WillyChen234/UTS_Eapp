<?php
// facility_detail.php - Rincian Lengkap Fasilitas Kampus USU
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM facilities WHERE id = ?");
$stmt->execute([$id]);
$facility = $stmt->fetch();

if (!$facility) {
    set_flash('danger', 'Fasilitas tidak ditemukan.');
    redirect_to(BASE_URL . "/facilities.php");
}

$pageTitle = $facility['name'];
require_once __DIR__ . '/includes/header.php';

// Ambil jadwal pemakaian terdekat
$stmtSchedule = $db->prepare("
    SELECT booking_code, booking_date, start_time, end_time, purpose, organization_type
    FROM bookings
    WHERE facility_id = ? AND booking_status = 'approved' AND booking_date >= CURRENT_DATE
    ORDER BY booking_date ASC, start_time ASC
    LIMIT 5
");
$stmtSchedule->execute([$id]);
$upcomingSchedules = $stmtSchedule->fetchAll();

$amenityList = array_map('trim', explode(',', $facility['amenities'] ?? ''));
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/facilities.php">Katalog Fasilitas</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($facility['code']) ?></li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill mb-2">
                    <?= htmlspecialchars($facility['category']) ?>
                </span>
                <h1 class="h2 fw-bold text-dark mb-1"><?= htmlspecialchars($facility['name']) ?></h1>
                <p class="text-muted mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($facility['building']) ?></p>
            </div>
            <div>
                <?php if ($facility['status'] === 'available'): ?>
                    <a href="<?= BASE_URL ?>/booking.php?facility_id=<?= $facility['id'] ?>" class="btn btn-primary-usu btn-lg shadow">
                        <i class="bi bi-calendar-check"></i> Booking Fasilitas Ini
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg" disabled>Sedang Maintenance</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Kolom Kiri: Foto & Detail -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($facility['image']) ?>" alt="<?= htmlspecialchars($facility['name']) ?>" class="img-fluid" style="width:100%; max-height:480px; object-fit:cover;">
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h4 class="fw-bold text-dark mb-3">Deskripsi Fasilitas</h4>
                <p class="text-secondary leading-relaxed mb-4">
                    <?= nl2br(htmlspecialchars($facility['description'])) ?>
                </p>

                <h4 class="fw-bold text-dark mb-3">Fasilitas & Sarana Penunjang</h4>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php foreach ($amenityList as $amenity): if (empty($amenity)) continue; ?>
                        <div class="d-flex align-items-center gap-2 p-2 px-3 bg-light border rounded-pill small fw-semibold text-dark">
                            <i class="bi bi-check-circle-fill text-success"></i> <?= htmlspecialchars($amenity) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-info border-0 rounded-3 d-flex align-items-center gap-3">
                    <i class="bi bi-info-circle-fill fs-3 text-info"></i>
                    <div class="small">
                        <strong>Ketentuan Peminjaman:</strong> Wajib melampirkan izin kegiatan dari Ketua Prodi / Wadek I untuk kegiatan internal ormawa, atau menyelesaikan pembayaran simulasi e-payment untuk acara berbayar.
                    </div>
                </div>
            </div>

            <!-- Jadwal Terdekat -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i> Jadwal Pemakaian Terdekat</h5>
                    <a href="<?= BASE_URL ?>/calendar.php?facility_id=<?= $facility['id'] ?>" class="small text-primary fw-semibold">Lihat Semua di Kalender &rarr;</a>
                </div>

                <?php if (empty($upcomingSchedules)): ?>
                    <p class="text-muted small mb-0">Belum ada pemakaian terjadwal untuk fasilitas ini. Slot jam masih sangat fleksibel!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Pukul</th>
                                    <th>Kegiatan / Acara</th>
                                    <th>Penyelenggara</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingSchedules as $sch): ?>
                                    <tr>
                                        <td class="fw-bold"><?= tgl_indo($sch['booking_date']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= substr($sch['start_time'], 0, 5) ?> - <?= substr($sch['end_time'], 0, 5) ?> WIB</span></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($sch['purpose']) ?></td>
                                        <td><span class="badge bg-success-subtle text-success"><?= htmlspecialchars($sch['organization_type']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Card Ringkasan & Booking -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:90px;">
                <h5 class="fw-bold text-dark mb-3">Ringkasan Biaya</h5>
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <span class="text-muted small">Tarif Sewa:</span>
                        <div>
                            <span class="fs-4 fw-black text-success"><?= rupiah($facility['hourly_rate']) ?></span>
                            <?php if ($facility['hourly_rate'] > 0): ?>
                                <small class="text-muted">/ jam</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        *Gratis untuk perkuliahan resmi dan praktikum terjadwal D3 TI USU.
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted"><i class="bi bi-people me-2"></i> Kapasitas Maksimal</span>
                        <span class="fw-bold text-dark"><?= $facility['capacity'] ?> Orang</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted"><i class="bi bi-building me-2"></i> Gedung</span>
                        <span class="fw-bold text-dark"><?= htmlspecialchars($facility['building']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted"><i class="bi bi-shield-check me-2"></i> Status</span>
                        <span class="fw-bold text-success"><?= $facility['status'] == 'available' ? 'Tersedia' : 'Maintenance' ?></span>
                    </div>
                </div>

                <?php if ($facility['status'] === 'available'): ?>
                    <a href="<?= BASE_URL ?>/booking.php?facility_id=<?= $facility['id'] ?>" class="btn btn-primary-usu w-100 justify-content-center py-3 mb-2">
                        <i class="bi bi-calendar-plus-fill"></i> Mulai Ajukan Booking
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary w-100 py-3 mb-2" disabled>Fasilitas Tidak Tersedia</button>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/calendar.php?facility_id=<?= $facility['id'] ?>" class="btn btn-outline-secondary w-100 justify-content-center small">
                    <i class="bi bi-calendar3"></i> Cek Kalender Slot
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
