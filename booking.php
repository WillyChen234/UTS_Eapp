<?php
// booking.php - Formulir Pengajuan E-Booking Fasilitas Kampus USU
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/notification_helper.php';

require_login(BASE_URL . '/booking.php' . (isset($_GET['facility_id']) ? '?facility_id=' . (int)$_GET['facility_id'] : ''));

$currentUser = current_user();
$selectedFacilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

// Ambil daftar fasilitas
$stmtFac = $db->query("SELECT * FROM facilities WHERE status = 'available' ORDER BY category ASC, name ASC");
$facilities = $stmtFac->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facilityId = (int)($_POST['facility_id'] ?? 0);
    $bookingDate = trim($_POST['booking_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $organizationType = trim($_POST['organization_type'] ?? 'Mahasiswa');
    $attendeeCount = (int)($_POST['attendee_count'] ?? 1);
    $additionalItems = isset($_POST['additional_facilities']) ? implode(', ', (array)$_POST['additional_facilities']) : '';

    // Validasi dasar
    if (!$facilityId || !$bookingDate || !$startTime || !$endTime || empty($purpose)) {
        set_flash('danger', 'Mohon lengkapi seluruh kolom yang wajib diisi.');
    } elseif ($startTime >= $endTime) {
        set_flash('danger', 'Jam selesai kegiatan harus lebih besar dari jam mulai.');
    } elseif ($bookingDate < date('Y-m-d')) {
        set_flash('danger', 'Tanggal booking tidak boleh di masa lampau.');
    } else {
        // Cek konflik anti-bentrok server-side
        $sqlConflict = "
            SELECT booking_code, start_time, end_time, purpose 
            FROM bookings 
            WHERE facility_id = ? 
              AND booking_date = ? 
              AND booking_status IN ('approved', 'pending')
              AND (? < end_time AND ? > start_time)
        ";
        $stmtConf = $db->prepare($sqlConflict);
        $stmtConf->execute([$facilityId, $bookingDate, $startTime, $endTime]);
        $conflict = $stmtConf->fetch();

        if ($conflict) {
            set_flash('danger', "Jadwal bentrok dengan kegiatan '{$conflict['purpose']}' (" . substr($conflict['start_time'], 0, 5) . " - " . substr($conflict['end_time'], 0, 5) . " WIB). Silakan pilih slot lain.");
        } else {
            // Ambil data fasilitas terpilih
            $stmtF = $db->prepare("SELECT * FROM facilities WHERE id = ?");
            $stmtF->execute([$facilityId]);
            $facilityData = $stmtF->fetch();

            // Hitung durasi jam
            $startTs = strtotime("2000-01-01 $startTime");
            $endTs = strtotime("2000-01-01 $endTime");
            $durationHours = round(($endTs - $startTs) / 3600, 2);

            // Hitung total biaya (jika akademik murni = gratis, jika berbayar = durasi * tarif)
            $totalAmount = ($facilityData['hourly_rate'] > 0) ? ($durationHours * $facilityData['hourly_rate']) : 0;
            $paymentStatus = ($totalAmount == 0) ? 'free_academic' : 'unpaid';
            $paymentMethod = ($totalAmount == 0) ? 'free_academic' : 'qris';

            // Generate Booking Code & QR Token
            $randomDigits = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $bookingCode = 'USU-BK-' . date('Ym') . '-' . $randomDigits;
            $qrToken = 'QR-' . $bookingCode . '-' . bin2hex(random_bytes(8));

            // Simpan ke database
            $sqlInsert = "
                INSERT INTO bookings (
                    booking_code, user_id, facility_id, booking_date, start_time, end_time,
                    duration_hours, purpose, organization_type, attendee_count, additional_facilities,
                    total_amount, payment_status, payment_method, booking_status, qr_code_token, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, " . ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now', 'localtime')" : "NOW()") . ")
            ";

            $stmtIns = $db->prepare($sqlInsert);
            $stmtIns->execute([
                $bookingCode, $currentUser['id'], $facilityId, $bookingDate, $startTime, $endTime,
                $durationHours, $purpose, $organizationType, $attendeeCount, $additionalItems,
                $totalAmount, $paymentStatus, $paymentMethod, $qrToken
            ]);

            $newBookingId = $db->lastInsertId();

            // Kirim notifikasi simulasi
            $bookingRecord = [
                'id' => $newBookingId,
                'booking_code' => $bookingCode,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_amount' => $totalAmount
            ];
            notify_booking_created($bookingRecord, $currentUser, $facilityData);

            set_flash('success', "Permohonan booking berhasil dibuat dengan kode {$bookingCode}!");
            redirect_to(BASE_URL . "/checkout.php?code=" . $bookingCode);
        }
    }
}

$pageTitle = "Formulir Booking Fasilitas";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/facilities.php">Fasilitas</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengajuan Booking</li>
            </ol>
        </nav>
        <h1 class="h2 fw-bold text-dark mb-1">Formulir Pengajuan E-Booking Fasilitas</h1>
        <p class="text-muted mb-0">Isi detail waktu dan peruntukan kegiatan Anda. Sistem kami mendeteksi ketersediaan secara real-time.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Form Kolom Kiri -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form action="<?= BASE_URL ?>/booking.php" method="POST" id="bookingForm">
                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom">
                        <i class="bi bi-1-circle-fill text-success me-2"></i> Pilihan Fasilitas & Waktu
                    </h5>

                    <!-- Pilihan Fasilitas -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Pilih Fasilitas Kampus <span class="text-danger">*</span></label>
                        <select name="facility_id" id="facility_id" class="form-select form-select-lg" required>
                            <option value="">-- Pilih Fasilitas --</option>
                            <?php foreach ($facilities as $fac): ?>
                                <option value="<?= $fac['id'] ?>" 
                                        data-rate="<?= $fac['hourly_rate'] ?>" 
                                        data-capacity="<?= $fac['capacity'] ?>"
                                        data-building="<?= htmlspecialchars($fac['building']) ?>"
                                        <?= $selectedFacilityId == $fac['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($fac['name']) ?> (<?= htmlspecialchars($fac['category']) ?> - <?= rupiah($fac['hourly_rate']) ?>/jam)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tanggal & Jam Slot -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control form-control-lg" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" class="form-control form-control-lg" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Jam Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" class="form-control form-control-lg" value="12:00" required>
                        </div>
                    </div>

                    <!-- Live Conflict Indicator Alert -->
                    <div id="slotCheckerBox" class="alert alert-light border d-flex align-items-center gap-3 py-3 mb-4 rounded-3">
                        <div id="slotStatusSpinner" class="spinner-border spinner-border-sm text-success" role="status" style="display:none;"></div>
                        <i id="slotStatusIcon" class="bi bi-shield-check fs-4 text-success"></i>
                        <div id="slotStatusText" class="small fw-semibold text-dark">
                            Pilih fasilitas, tanggal, dan jam untuk mengecek ketersediaan anti-bentrok secara otomatis.
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-4 pb-2 border-bottom pt-3">
                        <i class="bi bi-2-circle-fill text-success me-2"></i> Peruntukan Kegiatan & Penyelenggara
                    </h5>

                    <!-- Nama Acara -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Nama Acara / Keperluan Kegiatan <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" class="form-control form-control-lg" placeholder="Contoh: Workshop Cloud Architecture HIMATIF 2026" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Kategori Penyelenggara <span class="text-danger">*</span></label>
                            <select name="organization_type" class="form-select form-select-lg" required>
                                <option value="Himpunan / Ormawa">Himpunan Mahasiswa (HIMATIF / Ormawa)</option>
                                <option value="Mahasiswa / Kelas">Mahasiswa / Angkatan Kelas D3 TI</option>
                                <option value="Akademik / Prodi">Akademik / Program Studi / Dosen</option>
                                <option value="UKM Kampus">Unit Kegiatan Mahasiswa (UKM)</option>
                                <option value="Eksternal / Mitra">Instansi Luar / Mitra Kerjasama</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark">Estimasi Jumlah Peserta <span class="text-danger">*</span></label>
                            <input type="number" name="attendee_count" id="attendee_count" class="form-control form-control-lg" min="1" max="500" value="40" required>
                            <div class="form-text small" id="capacityHint">Kapasitas maksimal ruangan akan diperiksa.</div>
                        </div>
                    </div>

                    <!-- Fasilitas Tambahan Checkboxes -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark mb-2">Permintaan Sarana & Fasilitas Tambahan (Opsional):</label>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="form-check p-2 bg-light rounded border">
                                    <input class="form-check-input ms-1" type="checkbox" name="additional_facilities[]" value="Sound System & Mic Wireless" id="add1" checked>
                                    <label class="form-check-label ms-2 small fw-semibold" for="add1">Sound System & Mic Wireless</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check p-2 bg-light rounded border">
                                    <input class="form-check-input ms-1" type="checkbox" name="additional_facilities[]" value="Proyektor & Smart Display 4K" id="add2" checked>
                                    <label class="form-check-label ms-2 small fw-semibold" for="add2">Proyektor & Smart Display 4K</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check p-2 bg-light rounded border">
                                    <input class="form-check-input ms-1" type="checkbox" name="additional_facilities[]" value="Bantuan Operator Teknis Sarpras" id="add3">
                                    <label class="form-check-label ms-2 small fw-semibold" for="add3">Bantuan Operator Teknis Sarpras</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-check p-2 bg-light rounded border">
                                    <input class="form-check-input ms-1" type="checkbox" name="additional_facilities[]" value="Podium Resmi Logo USU" id="add4">
                                    <label class="form-check-label ms-2 small fw-semibold" for="add4">Podium Resmi Logo USU</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>/facilities.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
                        <button type="submit" id="btnSubmitBooking" class="btn btn-primary-usu btn-lg shadow">
                            Lanjut ke Checkout & Pembayaran <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Estimasi Biaya -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top bg-white" style="top:90px;">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt text-success me-2"></i> Estimasi Transaksi</h5>
                
                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="small text-muted">Pemohon:</div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($currentUser['name']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($currentUser['department'] ?: 'D3 TI Vokasi USU') ?></div>
                    <div class="small text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($currentUser['phone']) ?></div>
                </div>

                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Tarif Sewa Fasilitas:</span>
                    <span class="fw-bold text-dark" id="displayRate">Rp 0 / jam</span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Durasi Pemakaian:</span>
                    <span class="fw-bold text-dark" id="displayDuration">3.0 Jam</span>
                </div>
                <div class="py-2 border-bottom d-flex justify-content-between small">
                    <span class="text-muted">Biaya Administrasi Sistem:</span>
                    <span class="fw-bold text-success">Gratis (Rp 0)</span>
                </div>

                <div class="p-3 bg-success-subtle rounded-3 my-3 text-center">
                    <div class="small text-success-emphasis fw-semibold">Total Estimasi Pembayaran:</div>
                    <div class="fs-3 fw-black text-success" id="displayTotal">Rp 0</div>
                </div>

                <div class="alert alert-warning border-0 rounded-3 p-3 small mb-0">
                    <i class="bi bi-exclamation-circle-fill text-warning me-1"></i>
                    <strong>Penting:</strong> Slot waktu akan di-reservasi sementara dan memerlukan approval admin sarpras setelah pembayaran simulasi diselesaikan.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const facilitySelect = document.getElementById('facility_id');
    const dateInput = document.getElementById('booking_date');
    const startInput = document.getElementById('start_time');
    const endInput = document.getElementById('end_time');
    const attendeeInput = document.getElementById('attendee_count');
    
    const slotCheckerBox = document.getElementById('slotCheckerBox');
    const slotStatusSpinner = document.getElementById('slotStatusSpinner');
    const slotStatusIcon = document.getElementById('slotStatusIcon');
    const slotStatusText = document.getElementById('slotStatusText');
    const btnSubmit = document.getElementById('btnSubmitBooking');
    
    const displayRate = document.getElementById('displayRate');
    const displayDuration = document.getElementById('displayDuration');
    const displayTotal = document.getElementById('displayTotal');
    const capacityHint = document.getElementById('capacityHint');

    function formatRupiah(num) {
        if (num === 0) return 'Gratis (Akademik)';
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function calculateCost() {
        const selectedOpt = facilitySelect.options[facilitySelect.selectedIndex];
        const rate = selectedOpt && selectedOpt.dataset.rate ? parseFloat(selectedOpt.dataset.rate) : 0;
        const capacity = selectedOpt && selectedOpt.dataset.capacity ? parseInt(selectedOpt.dataset.capacity) : 0;
        
        if (capacity > 0) {
            capacityHint.innerHTML = `Kapasitas maksimal ruangan ini: <strong>${capacity} Orang</strong>.`;
            if (parseInt(attendeeInput.value) > capacity) {
                capacityHint.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle"></i> Jumlah peserta melebihi kapasitas ruangan (${capacity} org)!</span>`;
            }
        }

        const sVal = startInput.value;
        const eVal = endInput.value;

        if (sVal && eVal && sVal < eVal) {
            const startParts = sVal.split(':').map(Number);
            const endParts = eVal.split(':').map(Number);
            const duration = (endParts[0] + endParts[1]/60) - (startParts[0] + startParts[1]/60);
            
            displayDuration.textContent = duration.toFixed(1) + ' Jam';
            displayRate.textContent = rate > 0 ? (formatRupiah(rate) + ' / jam') : 'Gratis';
            
            const total = rate > 0 ? (duration * rate) : 0;
            displayTotal.textContent = formatRupiah(total);
        } else {
            displayDuration.textContent = '0 Jam';
            displayTotal.textContent = 'Rp 0';
        }
    }

    let checkTimeout = null;
    function checkSlotAvailability() {
        const facId = facilitySelect.value;
        const bDate = dateInput.value;
        const sTime = startInput.value;
        const eTime = endInput.value;

        calculateCost();

        if (!facId || !bDate || !sTime || !eTime) {
            slotCheckerBox.className = 'alert alert-light border d-flex align-items-center gap-3 py-3 mb-4 rounded-3';
            slotStatusIcon.className = 'bi bi-info-circle fs-4 text-secondary';
            slotStatusText.textContent = 'Pilih fasilitas, tanggal, dan jam untuk validasi ketersediaan.';
            btnSubmit.disabled = false;
            return;
        }

        if (sTime >= eTime) {
            slotCheckerBox.className = 'alert alert-danger border d-flex align-items-center gap-3 py-3 mb-4 rounded-3';
            slotStatusIcon.className = 'bi bi-x-circle-fill fs-4 text-danger';
            slotStatusText.textContent = 'Jam selesai harus lebih besar dari jam mulai!';
            btnSubmit.disabled = true;
            return;
        }

        // Show spinner
        slotStatusSpinner.style.display = 'inline-block';
        slotStatusIcon.style.display = 'none';
        slotStatusText.textContent = 'Memeriksa ketersediaan jadwal...';

        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(() => {
            fetch(`<?= BASE_URL ?>/api/check_slot.php?facility_id=${facId}&date=${bDate}&start_time=${sTime}&end_time=${eTime}`)
                .then(res => res.json())
                .then(data => {
                    slotStatusSpinner.style.display = 'none';
                    slotStatusIcon.style.display = 'inline-block';

                    if (data.available) {
                        slotCheckerBox.className = 'alert alert-success border d-flex align-items-center gap-3 py-3 mb-4 rounded-3';
                        slotStatusIcon.className = 'bi bi-check-circle-fill fs-4 text-success';
                        slotStatusText.textContent = data.message;
                        btnSubmit.disabled = false;
                    } else {
                        slotCheckerBox.className = 'alert alert-danger border d-flex align-items-center gap-3 py-3 mb-4 rounded-3';
                        slotStatusIcon.className = 'bi bi-exclamation-triangle-fill fs-4 text-danger';
                        slotStatusText.textContent = data.message;
                        btnSubmit.disabled = true;
                    }
                })
                .catch(err => {
                    slotStatusSpinner.style.display = 'none';
                    slotStatusIcon.style.display = 'inline-block';
                    slotStatusText.textContent = 'Gagal memverifikasi slot online.';
                });
        }, 300);
    }

    facilitySelect.addEventListener('change', checkSlotAvailability);
    dateInput.addEventListener('change', checkSlotAvailability);
    startInput.addEventListener('change', checkSlotAvailability);
    endInput.addEventListener('change', checkSlotAvailability);
    attendeeInput.addEventListener('input', calculateCost);

    // Initial check
    checkSlotAvailability();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
