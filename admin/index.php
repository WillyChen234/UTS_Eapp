<?php
// admin/index.php - Dashboard Administrator Sarpras Vokasi USU
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();
$currentUser = current_user();

$pageTitle = "Dashboard Admin";
require_once __DIR__ . '/../includes/header.php';

// KPI Metrics
$totalFacilities = $db->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
$pendingBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
$approvedBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'approved'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn();

// 5 Booking Terkini Menunggu Approval
$stmtRecent = $db->query("
    SELECT b.*, f.name as facility_name, f.category as facility_category, u.name as borrower_name, u.phone
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.id DESC
    LIMIT 6
");
$recentBookings = $stmtRecent->fetchAll();

// Statistik Fasilitas Terpopuler untuk Chart
$stmtPop = $db->query("
    SELECT f.name, COUNT(b.id) as count
    FROM facilities f
    LEFT JOIN bookings b ON f.id = b.facility_id
    GROUP BY f.id, f.name
    ORDER BY count DESC
");
$facilityPopularity = $stmtPop->fetchAll();
$popLabels = array_column($facilityPopularity, 'name');
$popCounts = array_column($facilityPopularity, 'count');
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-gold text-dark fw-bold px-3 py-1 rounded-pill mb-1" style="background:#fef3c7; color:#92400e;">
                    <i class="bi bi-shield-lock-fill"></i> Panel Administrator BMN & Sarpras
                </span>
                <h1 class="h2 fw-bold text-dark mb-0">Dashboard Pengelolaan Fasilitas Kampus</h1>
                <p class="text-muted small mb-0">Selamat datang, <?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['department']) ?>)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-secondary-usu btn-sm">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </a>
                <a href="<?= BASE_URL ?>/admin/facility_form.php" class="btn btn-primary-usu btn-sm">
                    <i class="bi bi-plus-circle"></i> Tambah Fasilitas Baru
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Stat Cards KPI -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 col-sm-6">
            <div class="admin-card-stat">
                <div class="stat-icon-wrapper bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="small text-muted fw-bold">Perlu Tindakan</div>
                    <div class="fs-3 fw-black text-warning-emphasis"><?= $pendingBookings ?></div>
                    <div class="small text-muted" style="font-size:0.75rem;">Menunggu Approval</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-card-stat">
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div>
                    <div class="small text-muted fw-bold">Jadwal Aktif</div>
                    <div class="fs-3 fw-black text-success"><?= $approvedBookings ?></div>
                    <div class="small text-muted" style="font-size:0.75rem;">Booking Disetujui</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-card-stat">
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="small text-muted fw-bold">Total Retribusi Kas</div>
                    <div class="fs-4 fw-black text-primary"><?= rupiah($totalRevenue) ?></div>
                    <div class="small text-muted" style="font-size:0.75rem;">E-Payment Terverifikasi</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="admin-card-stat">
                <div class="stat-icon-wrapper bg-secondary-subtle text-secondary">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <div class="small text-muted fw-bold">Total Fasilitas</div>
                    <div class="fs-3 fw-black text-dark"><?= $totalFacilities ?></div>
                    <div class="small text-muted" style="font-size:0.75rem;">Lab, Aula & Lapangan</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Analytics Section -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-bar-chart-fill text-success me-2"></i> Frekuensi Peminjaman Fasilitas</h5>
                        <p class="small text-muted mb-0">Perbandingan jumlah permohonan kegiatan per fasilitas kampus</p>
                    </div>
                </div>
                <div style="height: 280px; position:relative;">
                    <canvas id="facilityChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Komposisi Status</h5>
                <p class="small text-muted mb-3">Distribusi status booking pemohon</p>
                <div style="height: 220px; position:relative;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="pt-3 border-top mt-3 small text-center text-muted">
                    Sistem pemantauan anti-bentrok aktif 24/7
                </div>
            </div>
        </div>
    </div>

    <!-- Table Permohonan Terbaru Menunggu Tindakan -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1"><i class="bi bi-card-checklist text-warning me-2"></i> Permohonan Booking Terbaru</h5>
                <p class="small text-muted mb-0">Tinjau kelayakan berkas, bukti bayar, dan setujui / tolak peminjaman</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-secondary btn-sm">
                Lihat Seluruh Booking (<?= $totalFacilities ?>) &rarr;
            </a>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pemohon & Organisasi</th>
                        <th>Fasilitas & Jadwal</th>
                        <th>Total Biaya</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th class="text-center">Aksi Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentBookings)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data permohonan booking.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $rb): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($rb['booking_code']) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($rb['borrower_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($rb['organization_type']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($rb['facility_name']) ?></div>
                                    <div class="small text-muted">
                                        <?= tgl_indo($rb['booking_date']) ?> (<?= substr($rb['start_time'], 0, 5) ?> - <?= substr($rb['end_time'], 0, 5) ?>)
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= rupiah($rb['total_amount']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $rb['payment_status'] === 'paid' ? 'bg-success' : ($rb['payment_status'] === 'free_academic' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>">
                                        <?= strtoupper($rb['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($rb['booking_status'] === 'approved'): ?>
                                        <span class="badge-booking badge-approved">Disetujui</span>
                                    <?php elseif ($rb['booking_status'] === 'pending'): ?>
                                        <span class="badge-booking badge-pending">Pending</span>
                                    <?php elseif ($rb['booking_status'] === 'completed'): ?>
                                        <span class="badge-booking badge-completed">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge-booking badge-rejected">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($rb['booking_status'] === 'pending'): ?>
                                            <a href="<?= BASE_URL ?>/admin/booking_action.php?id=<?= $rb['id'] ?>&action=approve" class="btn btn-sm btn-success" title="Setujui Booking" onclick="return confirm('Setujui permohonan booking ini dan terbitkan E-Ticket QR?');">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $rb['id'] ?>" title="Tolak">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>/ticket.php?code=<?= htmlspecialchars($rb['booking_code']) ?>" class="btn btn-sm btn-outline-secondary" title="Lihat Tiket">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>

                                    <!-- Modal Tolak -->
                                    <div class="modal fade" id="rejectModal<?= $rb['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0">
                                                <form action="<?= BASE_URL ?>/admin/booking_action.php" method="POST">
                                                    <input type="hidden" name="booking_id" value="<?= $rb['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title fw-bold">Tolak Permohonan Booking</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-start p-4">
                                                        <p class="small text-muted mb-3">
                                                            Berikan alasan penolakan agar pemohon mendapatkan notifikasi WhatsApp/Email untuk perbaikan:
                                                        </p>
                                                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Contoh: Jadwal bentrok dengan acara Dies Natalis USU atau berkas izin kegiatan belum lengkap." required></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Facility Popularity Bar Chart
    const ctxFac = document.getElementById('facilityChart').getContext('2d');
    new Chart(ctxFac, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(function($n){ return strlen($n) > 20 ? substr($n,0,18).'...' : $n; }, $popLabels)) ?>,
            datasets: [{
                label: 'Jumlah Booking Acara',
                data: <?= json_encode($popCounts) ?>,
                backgroundColor: '#057a55',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 2. Status Doughnut Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Disetujui', 'Pending', 'Selesai'],
            datasets: [{
                data: [<?= (int)$approvedBookings ?>, <?= (int)$pendingBookings ?>, 1],
                backgroundColor: ['#10b981', '#f59e0b', '#3b82f6'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
