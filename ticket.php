<?php
// ticket.php - E-Ticket Resmi Ber-QR Code Fasilitas Kampus USU
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/qrcode_helper.php';

require_login();
$currentUser = current_user();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$stmt = $db->prepare("
    SELECT b.*, f.name as facility_name, f.category as facility_category, f.building, f.amenities,
           u.name as borrower_name, u.nim_nip, u.department, u.phone, u.email
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    JOIN users u ON b.user_id = u.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Tiket tidak ditemukan.');
    redirect_to(BASE_URL . "/my_bookings.php");
}

// User hanya boleh lihat tiketnya kecuali admin
if ($currentUser['role'] !== 'admin' && $booking['user_id'] != $currentUser['id']) {
    set_flash('danger', 'Anda tidak memiliki hak akses melihat tiket ini.');
    redirect_to(BASE_URL . "/my_bookings.php");
}

$pageTitle = "E-Ticket " . $booking['booking_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4 bg-light border-bottom no-print">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/my_bookings.php">Booking Saya</a></li>
                        <li class="breadcrumb-item active" aria-current="page">E-Ticket</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">E-Ticket & Akses Fasilitas Kampus</h1>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-secondary-usu">
                    <i class="bi bi-printer-fill"></i> Cetak / Simpan PDF
                </button>
                <a href="<?= BASE_URL ?>/verify.php?token=<?= urlencode($booking['qr_code_token']) ?>" target="_blank" class="btn btn-gold-usu">
                    <i class="bi bi-qr-code-scan"></i> Simulasi Scan QR
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Ticket Component Container -->
    <div class="ticket-wrapper">
        <!-- Header Tiket -->
        <div class="ticket-header">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-white text-success d-flex align-items-center justify-content-center shadow-sm" style="width:48px; height:48px; font-size:1.5rem;">
                    <i class="bi bi-building-check"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-white">UNIVERSITAS SUMATERA UTARA</h5>
                    <div class="small opacity-90">Fakultas Vokasi - Sarana & Prasarana D3 TI</div>
                </div>
            </div>
            <div class="text-end">
                <div class="small opacity-75">SURAT IZIN & E-TICKET</div>
                <div class="fw-black font-monospace fs-6 text-warning"><?= htmlspecialchars($booking['booking_code']) ?></div>
            </div>
        </div>

        <!-- Body Tiket -->
        <div class="ticket-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill">
                        <?= htmlspecialchars($booking['facility_category']) ?>
                    </span>
                    <h4 class="fw-bold text-dark mb-1 mt-2"><?= htmlspecialchars($booking['facility_name']) ?></h4>
                    <div class="text-muted small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($booking['building']) ?></div>
                </div>
                <div>
                    <?php if ($booking['booking_status'] === 'approved'): ?>
                        <span class="badge-booking badge-approved fs-6">
                            <i class="bi bi-check-circle-fill"></i> DISETUJUI
                        </span>
                    <?php elseif ($booking['booking_status'] === 'completed'): ?>
                        <span class="badge-booking badge-completed fs-6">
                            <i class="bi bi-mortarboard-fill"></i> SELESAI
                        </span>
                    <?php elseif ($booking['booking_status'] === 'pending'): ?>
                        <span class="badge-booking badge-pending fs-6">
                            <i class="bi bi-hourglass-split"></i> MENUNGGU
                        </span>
                    <?php else: ?>
                        <span class="badge-booking badge-rejected fs-6">
                            <i class="bi bi-x-circle-fill"></i> DITOLAK
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Perforation Divider -->
            <div class="ticket-perforation my-4">
                <div class="ticket-perforation-line"></div>
            </div>

            <!-- Detail Grid -->
            <div class="ticket-info-grid">
                <div>
                    <div class="ticket-label">Nama Peminjam / Penanggung Jawab</div>
                    <div class="ticket-value"><?= htmlspecialchars($booking['borrower_name']) ?></div>
                    <div class="small text-muted">NIM/NIP: <?= htmlspecialchars($booking['nim_nip'] ?: '-') ?></div>
                </div>
                <div>
                    <div class="ticket-label">Institusi / Himpunan / Unit</div>
                    <div class="ticket-value"><?= htmlspecialchars($booking['organization_type']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($booking['department'] ?: 'Vokasi USU') ?></div>
                </div>
                <div>
                    <div class="ticket-label">Hari & Tanggal Kegiatan</div>
                    <div class="ticket-value text-success"><?= tgl_indo($booking['booking_date']) ?></div>
                    <div class="small text-muted">Durasi: <?= $booking['duration_hours'] ?> Jam</div>
                </div>
                <div>
                    <div class="ticket-label">Waktu Operasional Slot</div>
                    <div class="ticket-value text-dark"><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?> WIB</div>
                    <div class="small text-muted">Check-in dibuka 15 menit sebelum acara</div>
                </div>
                <div style="grid-column: span 2;">
                    <div class="ticket-label">Keperluan Acara / Judul Kegiatan</div>
                    <div class="p-2 px-3 bg-light rounded-3 fw-bold text-dark border small mt-1">
                        <?= htmlspecialchars($booking['purpose']) ?>
                    </div>
                </div>
                <div>
                    <div class="ticket-label">Status Pembayaran E-Payment</div>
                    <div class="ticket-value text-uppercase text-primary">
                        <?= $booking['payment_status'] === 'paid' ? 'LUNAS (' . htmlspecialchars($booking['payment_method']) . ')' : ($booking['payment_status'] === 'free_academic' ? 'BEBAS BIAYA AKADEMIK' : 'BELUM LUNAS') ?>
                    </div>
                    <div class="small text-muted">Total: <?= rupiah($booking['total_amount']) ?></div>
                </div>
                <div>
                    <div class="ticket-label">Status Kehadiran di Lokasi</div>
                    <div class="ticket-value">
                        <?php if ($booking['check_in_status'] === 'checked_in'): ?>
                            <span class="badge bg-success"><i class="bi bi-check2-all"></i> CHECKED-IN</span>
                            <div class="small text-muted" style="font-size:0.7rem;"><?= $booking['checked_in_at'] ?></div>
                        <?php else: ?>
                            <span class="badge bg-secondary">BELUM SCAN MASUK</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Perforation Divider -->
            <div class="ticket-perforation my-4">
                <div class="ticket-perforation-line"></div>
            </div>

            <!-- QR Code Section -->
            <div class="text-center py-2 position-relative">
                <div class="mb-2 fw-bold text-dark small">KODE VERIFIKASI DIGITAL PETUGAS (SCAN QR)</div>
                <?= render_ticket_qrcode($booking['qr_code_token'], $booking['booking_code'], 200) ?>
                
                <p class="small text-muted mt-3 mb-0" style="max-width:440px; margin:0 auto; font-size:0.75rem;">
                    Perlihatkan tiket digital ini melalui layar smartphone atau cetakan fisik kepada petugas keamanan / teknisi lab saat memasuki fasilitas.
                </p>

                <!-- Stempel Resmi USU Simulation -->
                <?php if ($booking['booking_status'] === 'approved'): ?>
                    <div class="ticket-stamp">
                        ✓ APPROVED BMN USU
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
