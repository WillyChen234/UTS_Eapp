<?php
// checkout.php - Halaman Checkout & Simulasi E-Payment Fasilitas Kampus USU
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/notification_helper.php';

require_login();
$currentUser = current_user();

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$stmt = $db->prepare("
    SELECT b.*, f.name as facility_name, f.category as facility_category, f.building, f.hourly_rate
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    WHERE b.booking_code = ?
");
$stmt->execute([$code]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('danger', 'Data transaksi booking tidak ditemukan.');
    redirect_to(BASE_URL . "/my_bookings.php");
}

// User hanya boleh akses booking miliknya kecuali admin
if ($currentUser['role'] !== 'admin' && $booking['user_id'] != $currentUser['id']) {
    set_flash('danger', 'Akses transaksi tidak diizinkan.');
    redirect_to(BASE_URL . "/my_bookings.php");
}

// Jika sudah dibayar
if ($booking['payment_status'] === 'paid') {
    redirect_to(BASE_URL . "/payment_success.php?code=" . $booking['booking_code']);
}

// Handle Simulasi Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $method = $_POST['payment_method'] ?? 'qris';

    if ($action === 'simulate_success') {
        $refCode = strtoupper($method) . '-USU-' . mt_rand(1000000, 9999999);
        $paidAt = date('Y-m-d H:i:s');

        // Update database
        $stmtUp = $db->prepare("
            UPDATE bookings
            SET payment_status = 'paid',
                payment_method = ?,
                payment_ref = ?,
                paid_at = ?
            WHERE id = ?
        ");
        $stmtUp->execute([$method, $refCode, $paidAt, $booking['id']]);

        // Simpan notifikasi
        send_simulated_notification(
            $booking['user_id'],
            $booking['id'],
            'whatsapp',
            'Pembayaran Berhasil Diverifikasi',
            "Halo {$currentUser['name']}! Pembayaran sewa fasilitas {$booking['facility_name']} ({$booking['booking_code']}) sebesar " . rupiah($booking['total_amount']) . " via " . strtoupper($method) . " BERHASIL. Menunggu persetujuan Admin Sarpras.",
            $currentUser['phone']
        );

        set_flash('success', 'Pembayaran berhasil disimulasikan! Status pesanan kini telah DIBAYAR.');
        redirect_to(BASE_URL . "/payment_success.php?code=" . $booking['booking_code']);
    } elseif ($action === 'upload_proof') {
        $paymentRef = trim($_POST['manual_ref'] ?? ('TRF-' . mt_rand(100000, 999999)));
        $proofPath = 'uploads/payment_proofs/sample_transfer.png'; // default fallback

        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['proof_file']['tmp_name'];
            $name = 'proof_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $_FILES['proof_file']['name']);
            $targetDir = __DIR__ . '/uploads/payment_proofs';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $destination = $targetDir . '/' . $name;
            if (move_uploaded_file($tmp, $destination)) {
                $proofPath = 'uploads/payment_proofs/' . $name;
            }
        }

        $stmtUp = $db->prepare("
            UPDATE bookings
            SET payment_status = 'pending_verification',
                payment_method = 'transfer_manual',
                payment_ref = ?,
                payment_proof = ?
            WHERE id = ?
        ");
        $stmtUp->execute([$paymentRef, $proofPath, $booking['id']]);

        set_flash('success', 'Bukti pembayaran berhasil diunggah! Admin Sarpras akan memverifikasi berkas Anda.');
        redirect_to(BASE_URL . "/my_bookings.php");
    }
}

$pageTitle = "Checkout Pembayaran E-Payment";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/my_bookings.php">Booking Saya</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout E-Payment</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">E-Payment & Checkout Reservasi</h1>
                <p class="text-muted mb-0">Kode Reservasi: <strong class="text-success"><?= htmlspecialchars($booking['booking_code']) ?></strong></p>
            </div>
            <!-- Countdown Timer -->
            <div class="p-2 px-3 bg-white rounded-3 border shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-stopwatch text-danger fs-5"></i>
                <div>
                    <div class="small text-muted" style="font-size:0.7rem;">Selesaikan Dalam:</div>
                    <div class="fw-bold text-danger" id="countdownTimer">14:59 Menit</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Kolom Kiri: Pilihan Metode Pembayaran -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom">
                    <i class="bi bi-wallet2 text-success me-2"></i> Pilih Metode Transaksi Elektronik
                </h5>

                <?php if ($booking['total_amount'] == 0): ?>
                    <div class="alert alert-success p-4 rounded-4 text-center">
                        <i class="bi bi-patch-check-fill text-success display-4 mb-2 d-block"></i>
                        <h4 class="fw-bold">Bebas Biaya Sewa (Kegiatan Akademik)</h4>
                        <p class="small text-muted mb-4">
                            Kegiatan akademik, praktikum perkuliahan, dan pengabdian dosen tidak dikenakan retribusi sewa gedung.
                        </p>
                        <form method="POST">
                            <input type="hidden" name="action" value="simulate_success">
                            <input type="hidden" name="payment_method" value="free_academic">
                            <button type="submit" class="btn btn-primary-usu btn-lg px-5">
                                <i class="bi bi-check2-circle"></i> Konfirmasi Permohonan Akademik
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Opsi Metode E-Payment -->
                    <div class="row g-3 mb-4">
                        <!-- Opsi 1: QRIS -->
                        <div class="col-12">
                            <div class="payment-method-card selected" id="card-qris" onclick="selectPaymentMethod('qris')">
                                <div class="payment-icon-box text-danger">
                                    <i class="bi bi-qr-code"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-dark">QRIS Nasional (Instant Settlement)</div>
                                        <span class="badge bg-danger-subtle text-danger fw-bold">Rekomendasi</span>
                                    </div>
                                    <div class="small text-muted">BCA, Mandiri, BRI, BNI, GoPay, OVO, ShopeePay, DANA</div>
                                </div>
                                <input type="radio" name="method_choice" value="qris" class="form-check-input" checked>
                            </div>
                        </div>

                        <!-- Opsi 2: BNI Virtual Account -->
                        <div class="col-12">
                            <div class="payment-method-card" id="card-bni_va" onclick="selectPaymentMethod('bni_va')">
                                <div class="payment-icon-box text-warning">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-dark">BNI Virtual Account (Bank Resmi USU)</div>
                                        <span class="badge bg-light text-secondary border">Otomatis</span>
                                    </div>
                                    <div class="small text-muted">No. VA: <strong>98823240604501</strong> (Verifikasi Otomatis)</div>
                                </div>
                                <input type="radio" name="method_choice" value="bni_va" class="form-check-input">
                            </div>
                        </div>

                        <!-- Opsi 3: E-Wallet -->
                        <div class="col-12">
                            <div class="payment-method-card" id="card-gopay" onclick="selectPaymentMethod('gopay')">
                                <div class="payment-icon-box text-primary">
                                    <i class="bi bi-phone"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-dark">E-Wallet Kampus (GoPay / OVO)</div>
                                        <span class="badge bg-light text-secondary border">Instan</span>
                                    </div>
                                    <div class="small text-muted">Pembayaran langsung dari saldo dompet digital</div>
                                </div>
                                <input type="radio" name="method_choice" value="gopay" class="form-check-input">
                            </div>
                        </div>

                        <!-- Opsi 4: Transfer Bank Manual -->
                        <div class="col-12">
                            <div class="payment-method-card" id="card-transfer_manual" onclick="selectPaymentMethod('transfer_manual')">
                                <div class="payment-icon-box text-secondary">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-bold text-dark">Transfer Manual & Upload Bukti Bayar</div>
                                        <span class="badge bg-light text-secondary border">Manual Admin</span>
                                    </div>
                                    <div class="small text-muted">Bank Sumut Cabang USU No. Rek: 100-0204-998877 a.n BMN Vokasi USU</div>
                                </div>
                                <input type="radio" name="method_choice" value="transfer_manual" class="form-check-input">
                            </div>
                        </div>
                    </div>

                    <!-- Panel Tampilan Detail Metode Terpilih -->
                    <!-- 1. Tampilan QRIS -->
                    <div id="panel-qris" class="p-4 bg-light rounded-4 border text-center mb-4">
                        <div class="badge bg-dark text-white mb-2 px-3 py-1">QRIS STANDAR PEMBAYARAN NASIONAL</div>
                        <h6 class="fw-bold mb-3">Scan QRIS dengan Aplikasi Mobile Banking atau E-Wallet Anda</h6>
                        <div class="p-3 bg-white d-inline-block rounded-3 shadow-sm border mb-3">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('00020101021226680016ID.CO.USU.WWW0118936009882324060450520458125303360540' . $booking['total_amount'] . '5802ID5913USU_SMARTFACILITY6005MEDAN62150111' . $booking['booking_code'] . '6304') ?>" 
                                 alt="QRIS Simulator" style="width:190px; height:190px; display:block;" class="mx-auto">
                            <div class="small fw-bold text-danger mt-1">NMID: ID1020261988241</div>
                        </div>
                        <div class="fw-bold fs-4 text-success mb-2"><?= rupiah($booking['total_amount']) ?></div>
                        <div class="small text-muted">Nominal pembayaran pas sesuai total tagihan.</div>
                    </div>

                    <!-- 2. Tampilan BNI VA -->
                    <div id="panel-bni_va" class="p-4 bg-light rounded-4 border mb-4" style="display:none;">
                        <h6 class="fw-bold text-dark mb-2">Instruksi Pembayaran BNI Virtual Account</h6>
                        <div class="p-3 bg-white rounded-3 border mb-3">
                            <div class="small text-muted">Nomor Virtual Account USU:</div>
                            <div class="fs-4 fw-black text-primary font-monospace">988 232 4060 4501</div>
                            <div class="small text-muted">Nama Rekening: <strong>Sarpras Vokasi USU - <?= htmlspecialchars($currentUser['name']) ?></strong></div>
                        </div>
                        <ol class="small text-muted ps-3 mb-0">
                            <li>Buka BNI Mobile Banking / ATM BNI / Internet Banking.</li>
                            <li>Pilih menu <strong>Transfer > Virtual Account Billing</strong>.</li>
                            <li>Masukkan nomor VA di atas dan konfirmasi nama tagihan.</li>
                        </ol>
                    </div>

                    <!-- 3. Tampilan E-Wallet -->
                    <div id="panel-gopay" class="p-4 bg-light rounded-4 border text-center mb-4" style="display:none;">
                        <i class="bi bi-phone text-primary display-4 mb-2 d-block"></i>
                        <h6 class="fw-bold">Pembayaran Langsung GoPay / OVO</h6>
                        <p class="small text-muted">Notifikasi push akan dikirim ke nomor WhatsApp/HP terdaftar Anda: <strong><?= htmlspecialchars($currentUser['phone']) ?></strong></p>
                        <div class="fs-4 fw-bold text-dark mb-2"><?= rupiah($booking['total_amount']) ?></div>
                    </div>

                    <!-- 4. Tampilan Upload Bukti Manual -->
                    <div id="panel-transfer_manual" class="p-4 bg-light rounded-4 border mb-4" style="display:none;">
                        <h6 class="fw-bold text-dark mb-3">Formulir Upload Bukti Transfer Manual</h6>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_proof">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nomor Referensi Transfer / Bank Asal:</label>
                                <input type="text" name="manual_ref" class="form-control" placeholder="Contoh: TRF-BNI-992182 atau Nama Pengirim" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Unggah Foto Bukti Transfer (JPG, PNG, PDF):</label>
                                <input type="file" name="proof_file" class="form-control" accept="image/*,.pdf" required>
                            </div>
                            <button type="submit" class="btn btn-primary-usu w-100 justify-content-center">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Kirim Bukti Transfer ke Admin
                            </button>
                        </form>
                    </div>

                    <!-- Action Simulator Tombol Sukses -->
                    <div id="action-simulator-box" class="pt-3 border-top">
                        <form method="POST" id="simulatedPaymentForm">
                            <input type="hidden" name="action" value="simulate_success">
                            <input type="hidden" name="payment_method" id="selectedPaymentMethodInput" value="qris">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary-usu btn-lg justify-content-center py-3 shadow">
                                    <i class="bi bi-lightning-charge-fill text-warning"></i> Simulasi Bayar Sekarang (Simulator Sukses)
                                </button>
                                <a href="<?= BASE_URL ?>/my_bookings.php" class="btn btn-outline-secondary justify-content-center">
                                    Bayar Nanti (Simpan di Booking Saya)
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Tagihan -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top bg-white" style="top:90px;">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt-cutoff text-success me-2"></i> Rincian Pesanan</h5>
                
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Kode Booking:</span>
                        <span class="badge bg-dark font-monospace"><?= htmlspecialchars($booking['booking_code']) ?></span>
                    </div>
                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($booking['facility_name']) ?></div>
                    <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($booking['building']) ?></div>
                </div>

                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Tanggal Kegiatan:</span>
                    <span class="fw-bold text-dark"><?= tgl_indo($booking['booking_date']) ?></span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Waktu / Pukul:</span>
                    <span class="fw-bold text-dark"><?= substr($booking['start_time'], 0, 5) ?> - <?= substr($booking['end_time'], 0, 5) ?> WIB</span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Durasi:</span>
                    <span class="fw-bold text-dark"><?= $booking['duration_hours'] ?> Jam</span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Keperluan / Acara:</span>
                    <span class="fw-bold text-dark text-end" style="max-width:180px;"><?= htmlspecialchars($booking['purpose']) ?></span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Penyelenggara:</span>
                    <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($booking['organization_type']) ?></span>
                </div>

                <div class="p-3 bg-success-subtle rounded-3 my-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success-emphasis">Total Tagihan:</span>
                        <span class="fs-3 fw-black text-success"><?= rupiah($booking['total_amount']) ?></span>
                    </div>
                </div>

                <div class="small text-muted">
                    <i class="bi bi-shield-lock-fill text-success me-1"></i> Transaksi dilindungi oleh Enkripsi Token & Sistem Verifikasi Sarpras Kampus USU.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-method-card').forEach(el => el.classList.remove('selected'));
    const selectedCard = document.getElementById('card-' + method);
    if (selectedCard) selectedCard.classList.add('selected');

    // Set radio
    const radio = selectedCard.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;

    // Toggle panels
    ['qris', 'bni_va', 'gopay', 'transfer_manual'].forEach(m => {
        const p = document.getElementById('panel-' + m);
        if (p) p.style.display = (m === method) ? 'block' : 'none';
    });

    // Toggle simulator action box (hide if manual transfer chosen)
    const simBox = document.getElementById('action-simulator-box');
    if (simBox) {
        simBox.style.display = (method === 'transfer_manual') ? 'none' : 'block';
    }

    const inputVal = document.getElementById('selectedPaymentMethodInput');
    if (inputVal) inputVal.value = method;
}

// Countdown timer 15 mins
let secondsLeft = 14 * 60 + 59;
const timerEl = document.getElementById('countdownTimer');
if (timerEl) {
    setInterval(() => {
        if (secondsLeft <= 0) {
            timerEl.textContent = 'WAKTU HABIS';
            return;
        }
        secondsLeft--;
        const mins = Math.floor(secondsLeft / 60);
        const secs = secondsLeft % 60;
        timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')} Menit`;
    }, 1000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
