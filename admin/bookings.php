<?php
// admin/bookings.php - Manajemen Lengkap Seluruh Booking Fasilitas USU
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();

$pageTitle = "Kelola Booking";
require_once __DIR__ . '/../includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

$sql = "
    SELECT b.*, f.name as facility_name, f.category as facility_category, f.building,
           u.name as borrower_name, u.nim_nip, u.department, u.phone, u.email
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (b.booking_code LIKE ? OR b.purpose LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $status;
}

if ($facilityId > 0) {
    $sql .= " AND b.facility_id = ?";
    $params[] = $facilityId;
}

$sql .= " ORDER BY b.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// List Fasilitas untuk filter
$facList = $db->query("SELECT id, name FROM facilities ORDER BY name ASC")->fetchAll();
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kelola Booking</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">Manajemen & Approval E-Booking</h1>
                <p class="text-muted small mb-0">Verifikasi berkas, pantau ketersediaan slot, dan lakukan persetujuan penggunaan fasilitas.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-secondary-usu btn-sm">
                    <i class="bi bi-file-earmark-bar-graph"></i> Laporan & Rekapitulasi
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <form method="GET" action="<?= BASE_URL ?>/admin/bookings.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Cari Booking / Pemohon:</label>
                <input type="text" name="search" class="form-control" placeholder="Kode booking, nama acara, pemohon..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Status Persetujuan:</label>
                <select name="status" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending (Menunggu)</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved (Disetujui)</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed (Selesai)</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected (Ditolak)</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled (Batal)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter Fasilitas:</label>
                <select name="facility_id" class="form-select">
                    <option value="0">-- Semua Fasilitas --</option>
                    <?php foreach ($facList as $fl): ?>
                        <option value="<?= $fl['id'] ?>" <?= $facilityId == $fl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($fl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary-usu w-100 justify-content-center">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table Bookings -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pemohon</th>
                        <th>Fasilitas</th>
                        <th>Jadwal Kegiatan</th>
                        <th>Biaya & Pembayaran</th>
                        <th>Status</th>
                        <th class="text-center">Aksi & Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ditemukan data booking yang sesuai filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($b['booking_code']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($b['borrower_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($b['organization_type']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-whatsapp text-success"></i> <?= htmlspecialchars($b['phone']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($b['facility_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($b['building']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-success"><?= tgl_indo($b['booking_date']) ?></div>
                                    <div class="small text-dark fw-semibold"><?= substr($b['start_time'], 0, 5) ?> - <?= substr($b['end_time'], 0, 5) ?> WIB (<?= $b['duration_hours'] ?> Jam)</div>
                                    <div class="small text-muted text-truncate" style="max-width:200px;">Acara: <?= htmlspecialchars($b['purpose']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= rupiah($b['total_amount']) ?></div>
                                    <span class="badge <?= $b['payment_status'] === 'paid' ? 'bg-success' : ($b['payment_status'] === 'free_academic' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>">
                                        <?= strtoupper($b['payment_status']) ?> (<?= htmlspecialchars($b['payment_method']) ?>)
                                    </span>
                                    <?php if (!empty($b['payment_proof'])): ?>
                                        <div class="mt-1">
                                            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($b['payment_proof']) ?>" target="_blank" class="small text-primary fw-semibold">
                                                <i class="bi bi-file-earmark-image"></i> Cek Bukti Bayar
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['booking_status'] === 'approved'): ?>
                                        <span class="badge-booking badge-approved">Disetujui</span>
                                    <?php elseif ($b['booking_status'] === 'pending'): ?>
                                        <span class="badge-booking badge-pending">Pending</span>
                                    <?php elseif ($b['booking_status'] === 'completed'): ?>
                                        <span class="badge-booking badge-completed">Selesai</span>
                                    <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                        <span class="badge-booking badge-cancelled">Batal</span>
                                    <?php else: ?>
                                        <span class="badge-booking badge-rejected">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($b['booking_status'] === 'pending'): ?>
                                            <a href="<?= BASE_URL ?>/admin/booking_action.php?id=<?= $b['id'] ?>&action=approve" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Setujui permohonan ini dan kirimkan e-ticket QR Code?');">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $b['id'] ?>" title="Tolak">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php elseif ($b['booking_status'] === 'approved'): ?>
                                            <a href="<?= BASE_URL ?>/admin/booking_action.php?id=<?= $b['id'] ?>&action=complete" class="btn btn-sm btn-primary" title="Tandai Selesai" onclick="return confirm('Tandai kegiatan ini telah selesai dilaksanakan?');">
                                                <i class="bi bi-check2-all"></i> Selesai
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $b['id'] ?>" title="Rincian Berkas">
                                            <i class="bi bi-info-circle"></i>
                                        </button>

                                        <a href="<?= BASE_URL ?>/ticket.php?code=<?= htmlspecialchars($b['booking_code']) ?>" class="btn btn-sm btn-outline-dark" title="E-Ticket">
                                            <i class="bi bi-qr-code"></i>
                                        </a>
                                    </div>

                                    <!-- Modal Detail Berkas -->
                                    <div class="modal fade" id="detailModal<?= $b['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title fw-bold">Detail Berkas Booking</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start p-4">
                                                    <div class="small text-muted">Kode Booking:</div>
                                                    <div class="fw-bold font-monospace text-dark mb-3"><?= htmlspecialchars($b['booking_code']) ?></div>

                                                    <div class="small text-muted">Keperluan / Acara:</div>
                                                    <div class="fw-bold text-dark p-2 bg-light rounded border mb-3"><?= htmlspecialchars($b['purpose']) ?></div>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <div class="small text-muted">Estimasi Peserta:</div>
                                                            <div class="fw-bold text-dark"><?= $b['attendee_count'] ?> Orang</div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="small text-muted">Fasilitas Tambahan:</div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($b['additional_facilities'] ?: 'Tidak ada') ?></div>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($b['admin_notes'])): ?>
                                                        <div class="small text-muted">Catatan Admin:</div>
                                                        <div class="alert alert-light border small"><?= htmlspecialchars($b['admin_notes']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer bg-light border-0">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Tolak -->
                                    <div class="modal fade" id="rejectModal<?= $b['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0">
                                                <form action="<?= BASE_URL ?>/admin/booking_action.php" method="POST">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title fw-bold">Tolak Permohonan Booking</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start p-4">
                                                        <label class="form-label small fw-bold">Alasan Penolakan:</label>
                                                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Masukkan alasan penolakan..." required></textarea>
                                                    </div>
                                                    <div class="modal-footer bg-light border-0">
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger btn-sm">Tolak Booking</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
