<?php
// verify.php - Halaman Verifikasi Tiket & Check-In Mandiri Petugas Kampus
$pageTitle = "Verifikasi E-Ticket QR Code";
require_once __DIR__ . '/includes/header.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

$booking = null;
$error = null;

if (!empty($token)) {
    $stmt = $db->prepare("
        SELECT b.*, f.name as facility_name, f.category as facility_category, f.building,
               u.name as borrower_name, u.nim_nip, u.department, u.phone
        FROM bookings b
        JOIN facilities f ON b.facility_id = f.id
        JOIN users u ON b.user_id = u.id
        WHERE b.qr_code_token = ?
    ");
    $stmt->execute([$token]);
    $booking = $stmt->fetch();
} elseif (!empty($code)) {
    $stmt = $db->prepare("
        SELECT b.*, f.name as facility_name, f.category as facility_category, f.building,
               u.name as borrower_name, u.nim_nip, u.department, u.phone
        FROM bookings b
        JOIN facilities f ON b.facility_id = f.id
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_code = ?
    ");
    $stmt->execute([$code]);
    $booking = $stmt->fetch();
}

// Handle Check-In Action oleh Petugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'do_check_in' && $booking) {
    if ($booking['booking_status'] === 'approved') {
        $now = date('Y-m-d H:i:s');
        $stmtCheck = $db->prepare("
            UPDATE bookings
            SET check_in_status = 'checked_in',
                checked_in_at = ?
            WHERE id = ?
        ");
        $stmtCheck->execute([$now, $booking['id']]);

        // Refresh data
        $booking['check_in_status'] = 'checked_in';
        $booking['checked_in_at'] = $now;

        set_flash('success', "Check-in berhasil! Peminjam resmi terverifikasi memasuki {$booking['facility_name']}.");
    } else {
        set_flash('danger', "Gagal check-in! Status booking bukan 'approved'.");
    }
}
?>

<div class="py-4 bg-light border-bottom">
    <div class="container text-center max-w-700">
        <h1 class="h2 fw-bold text-dark mb-1"><i class="bi bi-shield-check text-success"></i> Verifikasi E-Ticket & QR Code</h1>
        <p class="text-muted mb-0">Sistem validasi keaslian tiket reservasi fasilitas Sarpras D3 Teknik Informatika Vokasi USU</p>
    </div>
</div>

<div class="container py-5">
    <div class="max-w-700 mx-auto" style="max-width:680px;">
        <!-- Input Form Pencarian Manual -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <label class="form-label fw-bold text-dark small mb-2"><i class="bi bi-search"></i> Cek Berdasarkan Kode Booking / Token QR:</label>
            <form action="<?= BASE_URL ?>/verify.php" method="GET" class="d-flex gap-2">
                <input type="text" name="code" class="form-control" placeholder="Contoh: USU-BK-202610-001" value="<?= htmlspecialchars($code ?: ($booking['booking_code'] ?? '')) ?>" required>
                <button type="submit" class="btn btn-primary-usu">
                    <i class="bi bi-search"></i> Verifikasi
                </button>
            </form>
        </div>

        <?php if (!empty($token) || !empty($code)): ?>
            <?php if ($booking): ?>
                <!-- Card Tiket Ditemukan -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white mb-4">
                    <div class="p-4 text-white text-center <?= $booking['booking_status'] === 'approved' ? 'bg-success' : ($booking['booking_status'] === 'completed' ? 'bg-primary' : 'bg-danger') ?>">
                        <?php if ($booking['booking_status'] === 'approved'): ?>
                            <i class="bi bi-check-circle-fill display-3 mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1">TIKET RESMI & TERVERIFIKASI</h3>
                            <div class="small opacity-90">Sistem Sarana & Prasarana Vokasi USU Menyatakan Tiket Ini Sah</div>
                        <?php elseif ($booking['booking_status'] === 'completed'): ?>
                            <i class="bi bi-mortarboard-fill display-3 mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1">ACARA SELESAI DIGELAR</h3>
                            <div class="small opacity-90">Jadwal pemakaian fasilitas ini telah rampung</div>
                        <?php else: ?>
                            <i class="bi bi-exclamation-triangle-fill display-3 mb-2 d-block"></i>
                            <h3 class="fw-bold mb-1">STATUS: <?= strtoupper($booking['booking_status']) ?></h3>
                            <div class="small opacity-90">Tiket ini belum atau tidak disetujui untuk pemakaian fasilitas</div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="p-3 bg-light rounded-3 mb-4 border">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="small text-muted">Kode Booking:</span>
                                <span class="badge bg-dark font-monospace"><?= htmlspecialchars($booking['booking_code']) ?></span>
                            </div>
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($booking['facility_name']) ?></h4>
                            <p class="small text-muted mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($booking['building']) ?></p>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="small text-muted">Penanggung Jawab:</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($booking['borrower_name']) ?></div>
                                <div class="small text-muted">NIM/NIP: <?= htmlspecialchars($booking['nim_nip'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Unit / Himpunan:</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($booking['organization_type']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($booking['department'] ?: '-') ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Tanggal Kegiatan:</div>
                                <div class="fw-bold text-success"><?= tgl_indo($booking['booking_date']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Jam Pemakaian:</div>
                                <div class="fw-bold text-dark"><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?> WIB</div>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Acara / Keperluan:</div>
                                <div class="fw-bold text-dark p-2 bg-light rounded border small"><?= htmlspecialchars($booking['purpose']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Status Pembayaran:</div>
                                <div class="fw-bold text-uppercase text-primary"><?= htmlspecialchars($booking['payment_status']) ?> (<?= rupiah($booking['total_amount']) ?>)</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Status Check-in Lokasi:</div>
                                <div class="fw-bold">
                                    <?php if ($booking['check_in_status'] === 'checked_in'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check2-all"></i> CHECKED-IN</span>
                                        <div class="small text-muted" style="font-size:0.75rem;">Pada: <?= $booking['checked_in_at'] ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">BELUM CHECK-IN</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Check-In Action Button untuk Petugas -->
                        <?php if ($booking['booking_status'] === 'approved'): ?>
                            <div class="pt-3 border-top text-center">
                                <?php if ($booking['check_in_status'] === 'checked_in'): ?>
                                    <div class="alert alert-success d-flex align-items-center justify-content-center gap-2 mb-0">
                                        <i class="bi bi-check-circle-fill"></i> Peminjam telah selesai diverifikasi masuk pada <?= $booking['checked_in_at'] ?>.
                                    </div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="do_check_in">
                                        <button type="submit" class="btn btn-primary-usu btn-lg w-100 justify-content-center shadow">
                                            <i class="bi bi-box-arrow-in-right"></i> Konfirmasi Check-In Hadir di Lokasi
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Found Alert -->
                <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
                    <i class="bi bi-x-octagon-fill text-danger display-3 mb-3 d-block"></i>
                    <h4 class="fw-bold text-dark">Data Tiket Tidak Ditemukan!</h4>
                    <p class="text-muted mb-4">
                        Kode token atau nomor booking yang Anda masukkan tidak terdaftar dalam database sistem USU SmartFacility.
                    </p>
                    <a href="<?= BASE_URL ?>/verify.php" class="btn btn-secondary-usu">Coba Kode Lain</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
