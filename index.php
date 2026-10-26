<?php
// index.php - Halaman Utama USU SmartFacility
$pageTitle = "Beranda";
require_once __DIR__ . '/includes/header.php';

// Ambil fasilitas unggulan
$stmt = $db->query("SELECT * FROM facilities ORDER BY id ASC LIMIT 4");
$featuredFacilities = $stmt->fetchAll();

// Ambil statistik ringkas
$totalFacilities = $db->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
$totalBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status IN ('approved', 'completed')")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="hero-badge">
                    <i class="bi bi-patch-check-fill text-success"></i> Sistem Resmi Sarpras Vokasi USU
                </div>
                <h1 class="hero-title">
                    Peminjaman Fasilitas Kampus Jadi <span class="text-highlight">Mudah, Cepat & Bebas Bentrok</span>
                </h1>
                <p class="hero-desc">
                    Layanan digital terintegrasi untuk pemesanan Laboratorium Komputer, Aula Serbaguna, Gelanggang Olahraga, dan Ruang Seminar di lingkungan D3 Teknik Informatika & Fakultas Vokasi USU dengan transparansi jadwal dan e-payment aman.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= BASE_URL ?>/facilities.php" class="btn btn-primary-usu btn-lg shadow">
                        <i class="bi bi-search"></i> Jelajahi Fasilitas
                    </a>
                    <a href="<?= BASE_URL ?>/calendar.php" class="btn btn-secondary-usu btn-lg">
                        <i class="bi bi-calendar-week"></i> Cek Jadwal Real-time
                    </a>
                </div>

                <!-- Stats Pills -->
                <div class="row g-3 mt-4 pt-2 border-top">
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-dark"><?= $totalFacilities ?>+</div>
                        <div class="small text-muted fw-semibold">Fasilitas Kampus</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-success"><?= $totalBookings ?>+</div>
                        <div class="small text-muted fw-semibold">Event Sukses</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-3 text-primary"><?= $totalUsers ?>+</div>
                        <div class="small text-muted fw-semibold">Mahasiswa & Ormawa</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="position-relative">
                    <div class="card border-0 shadow-lg overflow-hidden" style="border-radius:24px;">
                        <img src="<?= BASE_URL ?>/assets/images/lab_komputer.jpg" alt="Lab Komputer D3 TI" class="img-fluid" style="height:340px; object-fit:cover;">
                        <div class="card-body p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill">
                                    <i class="bi bi-cpu me-1"></i> Rekomendasi Prodi
                                </span>
                                <span class="fw-bold text-success fs-5">Rp 50.000 <small class="text-muted fs-6">/ jam</small></span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Lab Komputer & Software Engineering</h5>
                            <p class="small text-muted mb-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Gedung Vokasi USU Lantai 2 (Lab A)</p>
                            <a href="<?= BASE_URL ?>/booking.php?facility_id=1" class="btn btn-primary-usu w-100 justify-content-center">
                                <i class="bi bi-lightning-charge-fill text-warning"></i> Pesan Sekarang
                            </a>
                        </div>
                    </div>
                    
                    <!-- Floating Quick Tag -->
                    <div class="position-absolute d-none d-sm-flex align-items-center gap-3 bg-white p-3 rounded-4 shadow-lg border" style="bottom: -20px; left: -25px; max-width: 280px;">
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:48px; height:48px; font-size:1.5rem;">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div>
                            <div class="fw-bold small text-dark">E-Ticket QR Code</div>
                            <div class="text-muted" style="font-size:0.75rem;">Check-in otomatis tanpa kertas antre</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4 Langkah Mudah -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-3">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill mb-2">ALUR TRANSAKSI ELEKTRONIK</span>
            <h2 class="fw-bold text-dark">Bagaimana Cara Kerja USU SmartFacility?</h2>
            <p class="text-muted">Proses terstruktur yang menjamin keterbukaan slot jadwal tanpa risiko bentrok.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="p-4 rounded-4 border bg-light h-100 position-relative">
                    <span class="position-absolute top-0 end-0 m-3 fs-3 fw-black text-muted opacity-25">01</span>
                    <div class="rounded-3 bg-success text-white d-flex align-items-center justify-content-center mb-3" style="width:46px; height:46px; font-size:1.25rem;">
                        <i class="bi bi-search"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Pilih Fasilitas</h5>
                    <p class="small text-muted mb-0">Pilih Lab, Aula, Lapangan Olahraga, atau Ruang Seminar sesuai kapasitas dan kebutuhan kegiatan Anda.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-4 rounded-4 border bg-light h-100 position-relative">
                    <span class="position-absolute top-0 end-0 m-3 fs-3 fw-black text-muted opacity-25">02</span>
                    <div class="rounded-3 bg-warning text-white d-flex align-items-center justify-content-center mb-3" style="width:46px; height:46px; font-size:1.25rem;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Cek Slot Waktu</h5>
                    <p class="small text-muted mb-0">Sistem otomatis memverifikasi kalender real-time untuk memastikan slot jam yang Anda pilih belum terpakai.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-4 rounded-4 border bg-light h-100 position-relative">
                    <span class="position-absolute top-0 end-0 m-3 fs-3 fw-black text-muted opacity-25">03</span>
                    <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center mb-3" style="width:46px; height:46px; font-size:1.25rem;">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <h5 class="fw-bold text-dark">E-Payment</h5>
                    <p class="small text-muted mb-0">Simulasi pembayaran instan melalui QRIS, Virtual Account Bank (BNI/Mandiri/BRI), atau E-Wallet kampus.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-4 rounded-4 border bg-light h-100 position-relative">
                    <span class="position-absolute top-0 end-0 m-3 fs-3 fw-black text-muted opacity-25">04</span>
                    <div class="rounded-3 bg-dark text-white d-flex align-items-center justify-content-center mb-3" style="width:46px; height:46px; font-size:1.25rem;">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Dapatkan E-Ticket QR</h5>
                    <p class="small text-muted mb-0">Setelah disetujui, tiket digital resmi ber-QR Code terbit dan siap di-scan petugas saat memasuki fasilitas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Katalog Fasilitas Unggulan -->
<section class="py-5">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="badge bg-success-subtle text-success fw-bold px-3 py-2 rounded-pill mb-2">KATALOG PILIHAN</span>
                <h2 class="fw-bold text-dark mb-1">Fasilitas Kampus Siap Pakai</h2>
                <p class="text-muted mb-0">Sarana prasarana modern untuk mendukung kegiatan tri dharma dan ormawa mahasiswa.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= BASE_URL ?>/facilities.php" class="btn btn-secondary-usu">
                    Lihat Semua Fasilitas (<?= $totalFacilities ?>) <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredFacilities as $fac): ?>
                <div class="col-lg-6">
                    <div class="card-facility">
                        <div class="row g-0 h-100">
                            <div class="col-md-5 card-facility-img-wrap" style="height:100%; min-height:220px;">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($fac['image']) ?>" alt="<?= htmlspecialchars($fac['name']) ?>">
                                <span class="badge-category"><?= htmlspecialchars($fac['category']) ?></span>
                                <span class="badge-status-chip <?= $fac['status'] == 'available' ? 'badge-status-available' : 'badge-status-maintenance' ?>">
                                    <?= $fac['status'] == 'available' ? '✓ Tersedia' : 'Dalam Perbaikan' ?>
                                </span>
                            </div>
                            <div class="col-md-7 card-facility-body">
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
                                <p class="small text-muted mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($fac['description']) ?>
                                </p>
                                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                                    <span class="small fw-semibold text-secondary">
                                        <i class="bi bi-people-fill me-1 text-primary"></i> Kapasitas: <?= $fac['capacity'] ?> Orang
                                    </span>
                                    <a href="<?= BASE_URL ?>/booking.php?facility_id=<?= $fac['id'] ?>" class="btn btn-primary-usu btn-sm">
                                        <i class="bi bi-calendar-plus"></i> Booking
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Banner Edukasi Call to Action -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="p-5 rounded-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #03543f 0%, #057a55 100%); color:white;">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">AKADEMIK & ORMAWA USU</span>
                    <h2 class="display-6 fw-bold mb-3">Butuh Tempat untuk Kuliah Tamu, Sidang, atau Turnamen Kampus?</h2>
                    <p class="lead opacity-90 mb-0">
                        Cek jadwal ketersediaan di kalender real-time sekarang juga. Bebas bentrok, konfirmasi transparan, dan tiket QR code resmi dari Pengelola Sarpras Vokasi USU.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="<?= BASE_URL ?>/calendar.php" class="btn btn-gold-usu btn-lg shadow-lg me-2">
                        <i class="bi bi-calendar3"></i> Lihat Jadwal
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
