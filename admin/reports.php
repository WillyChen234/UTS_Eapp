<?php
// admin/reports.php - Laporan Pemakaian Fasilitas & Rekapitulasi Retribusi Kas USU
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();

$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-t');
$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';

// Query Laporan
$sql = "
    SELECT b.*, f.name as facility_name, f.category as facility_category, f.building,
           u.name as borrower_name, u.nim_nip, u.department, u.phone
    FROM bookings b
    JOIN facilities f ON b.facility_id = f.id
    JOIN users u ON b.user_id = u.id
    WHERE b.booking_date BETWEEN ? AND ?
";
$params = [$startDate, $endDate];

if ($facilityId > 0) {
    $sql .= " AND b.facility_id = ?";
    $params[] = $facilityId;
}

$sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Handle Export CSV (Excel)
if ($exportCsv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Pemakaian_Fasilitas_USU_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['No', 'Kode Booking', 'Tanggal', 'Pukul', 'Durasi (Jam)', 'Fasilitas', 'Kategori', 'Keperluan Acara', 'Peminjam', 'NIM/NIP', 'Organisasi', 'Metode Bayar', 'Total Biaya (Rp)', 'Status Bayar', 'Status Booking']);

    $no = 1;
    foreach ($reports as $r) {
        fputcsv($output, [
            $no++,
            $r['booking_code'],
            $r['booking_date'],
            substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5) . ' WIB',
            $r['duration_hours'],
            $r['facility_name'],
            $r['facility_category'],
            $r['purpose'],
            $r['borrower_name'],
            $r['nim_nip'],
            $r['organization_type'],
            $r['payment_method'],
            $r['total_amount'],
            $r['payment_status'],
            $r['booking_status']
        ]);
    }
    fclose($output);
    exit;
}

// Summary Metrics
$totalEvents = count($reports);
$totalHours = array_sum(array_column($reports, 'duration_hours'));
$totalRevenue = 0;
foreach ($reports as $r) {
    if ($r['payment_status'] === 'paid') {
        $totalRevenue += $r['total_amount'];
    }
}

$facList = $db->query("SELECT id, name FROM facilities ORDER BY name ASC")->fetchAll();

$pageTitle = "Laporan Pemakaian Fasilitas";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kop Surat Resmi Kampus (Hanya Muncul Saat Print) -->
<div class="print-only d-none py-3 border-bottom mb-4">
    <div class="text-center">
        <h5 class="fw-bold mb-0">KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h5>
        <h4 class="fw-bold mb-0">UNIVERSITAS SUMATERA UTARA</h4>
        <h5 class="fw-bold mb-1">FAKULTAS VOKASI - PROGRAM STUDI D3 TEKNIK INFORMATIKA</h5>
        <p class="small text-muted mb-0">Jalan Alumni No. 9 Kampus USU Padang Bulan Medan 20155 | Telp: (061) 8211234</p>
        <p class="small text-muted mb-0">Laman: vokasi.usu.ac.id | Surel: sarpras.vokasi@usu.ac.id</p>
        <hr class="my-2" style="border-top: 3px double #000;">
        <h5 class="fw-bold text-uppercase mt-3">REKAPITULASI LAPORAN PEMAKAIAN FASILITAS & RETRIBUSI</h5>
        <p class="small">Periode: <?= tgl_indo($startDate) ?> s/d <?= tgl_indo($endDate) ?></p>
    </div>
</div>

<div class="py-4 bg-light border-bottom no-print">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Laporan & Rekapitulasi</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">Laporan Pemakaian Fasilitas Kampus</h1>
                <p class="text-muted small mb-0">Rekapitulasi penggunaan laboratorium, aula, dan gelanggang olahraga beserta mutasi pendapatan e-payment.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/admin/reports.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&facility_id=<?= $facilityId ?>&export=csv" class="btn btn-success shadow-sm">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel (CSV)
                </a>
                <button onclick="window.print()" class="btn btn-secondary-usu">
                    <i class="bi bi-printer"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 no-print">
        <form method="GET" action="<?= BASE_URL ?>/admin/reports.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter Fasilitas:</label>
                <select name="facility_id" class="form-select">
                    <option value="0">-- Semua Fasilitas Kampus --</option>
                    <?php foreach ($facList as $fl): ?>
                        <option value="<?= $fl['id'] ?>" <?= $facilityId == $fl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($fl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary-usu w-100 justify-content-center">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-4 bg-white rounded-4 border shadow-sm">
                <div class="small text-muted fw-bold">Total Acara / Kegiatan:</div>
                <div class="fs-2 fw-black text-dark"><?= $totalEvents ?> <span class="fs-6 text-muted fw-normal">Kegiatan</span></div>
                <div class="small text-muted">Periode terpilih</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white rounded-4 border shadow-sm">
                <div class="small text-muted fw-bold">Total Jam Pemakaian:</div>
                <div class="fs-2 fw-black text-success"><?= number_format($totalHours, 1) ?> <span class="fs-6 text-muted fw-normal">Jam</span></div>
                <div class="small text-muted">Durasi aktif fasilitas</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white rounded-4 border shadow-sm">
                <div class="small text-muted fw-bold">Total Penerimaan Retribusi:</div>
                <div class="fs-2 fw-black text-primary"><?= rupiah($totalRevenue) ?></div>
                <div class="small text-muted">Setoran kas resmi BMN</div>
            </div>
        </div>
    </div>

    <!-- Tabel Rincian Pemakaian -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="custom-table table-bordered">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Kode</th>
                        <th>Tanggal & Jam</th>
                        <th>Fasilitas</th>
                        <th>Nama Kegiatan / Acara</th>
                        <th>Peminjam & Unit</th>
                        <th>Biaya Sewa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Tidak ada data transaksi pemakaian pada rentang tanggal tersebut.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($reports as $r): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['booking_code']) ?></span></td>
                                <td>
                                    <div class="fw-bold"><?= tgl_indo($r['booking_date']) ?></div>
                                    <div class="small text-muted"><?= substr($r['start_time'], 0, 5) ?> - <?= substr($r['end_time'], 0, 5) ?> WIB (<?= $r['duration_hours'] ?> Jam)</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($r['facility_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($r['building']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['purpose']) ?></div>
                                    <div class="small text-muted">Peserta: <?= $r['attendee_count'] ?> orang</div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['borrower_name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($r['organization_type']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= rupiah($r['total_amount']) ?></div>
                                    <div class="small text-uppercase text-secondary"><?= htmlspecialchars($r['payment_method']) ?></div>
                                </td>
                                <td>
                                    <?php if ($r['booking_status'] === 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php elseif ($r['booking_status'] === 'completed'): ?>
                                        <span class="badge bg-primary">Completed</span>
                                    <?php elseif ($r['booking_status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><?= ucfirst($r['booking_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tanda Tangan Pengesahan (Hanya Tampil Saat Print) -->
        <div class="print-only d-none mt-5 pt-4">
            <div class="row">
                <div class="col-8"></div>
                <div class="col-4 text-center">
                    <p class="mb-1">Medan, <?= tgl_indo(date('Y-m-d')) ?></p>
                    <p class="mb-5">Kepala Pengelola Sarpras Vokasi USU</p>
                    <div style="height: 60px;"></div>
                    <p class="fw-bold text-decoration-underline mb-0">Dr. Ir. Budi Rahardjo, M.Kom</p>
                    <p class="small text-muted mb-0">NIP. 198005122005011002</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
