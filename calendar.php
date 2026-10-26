<?php
// calendar.php - Kalender Ketersediaan Fasilitas Real-Time (FullCalendar.js)
$pageTitle = "Kalender Ketersediaan Fasilitas";
require_once __DIR__ . '/includes/header.php';

$facilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
$stmtFacilities = $db->query("SELECT id, name, category FROM facilities ORDER BY name ASC");
$allFacilities = $stmtFacilities->fetchAll();
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kalender Jadwal Real-time</li>
            </ol>
        </nav>
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <h1 class="h2 fw-bold text-dark mb-1">Kalender Ketersediaan Fasilitas Kampus</h1>
                <p class="text-muted mb-0">Pantau jadwal pemakaian fasilitas secara transparan untuk menghindari bentrok jadwal.</p>
            </div>
            <div class="col-md-5">
                <form method="GET" action="<?= BASE_URL ?>/calendar.php" class="d-flex align-items-center gap-2">
                    <label class="fw-bold small text-dark text-nowrap"><i class="bi bi-funnel"></i> Filter Fasilitas:</label>
                    <select name="facility_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">-- Semua Fasilitas Kampus --</option>
                        <?php foreach ($allFacilities as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $facilityId == $f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Category Color Legend -->
    <div class="d-flex flex-wrap align-items-center gap-3 p-3 bg-white rounded-4 border shadow-sm mb-4">
        <span class="small fw-bold text-muted"><i class="bi bi-info-circle me-1"></i> Kategori Fasilitas:</span>
        <span class="badge" style="background:#057a55; color:#fff; font-size:0.8rem; font-weight:600;">
            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Laboratorium D3 TI
        </span>
        <span class="badge" style="background:#d97706; color:#fff; font-size:0.8rem; font-weight:600;">
            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Aula Serbaguna
        </span>
        <span class="badge" style="background:#1d4ed8; color:#fff; font-size:0.8rem; font-weight:600;">
            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Lapangan Olahraga
        </span>
        <span class="badge" style="background:#7c3aed; color:#fff; font-size:0.8rem; font-weight:600;">
            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Ruang Seminar & Smart Class
        </span>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>/booking.php" class="btn btn-primary-usu btn-sm">
                <i class="bi bi-plus-circle"></i> Ajukan Booking Baru
            </a>
        </div>
    </div>

    <!-- Calendar Container Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div id="fullcalendar-view" style="min-height: 650px;"></div>
    </div>
</div>

<!-- Modal Detail Event Jadwal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header text-white" id="modalHeaderBg" style="background:#057a55;">
                <h5 class="modal-title fw-bold" id="modalTitle">Detail Jadwal Pemakaian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <span class="badge bg-light text-secondary border" id="modalBookingCode">USU-BK-XXXX</span>
                    <span class="badge bg-success-subtle text-success fw-bold" id="modalStatus">DISETUJUI</span>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-semibold">Nama Fasilitas:</label>
                    <div class="fw-bold fs-6 text-dark" id="modalFacilityName">-</div>
                    <div class="small text-muted" id="modalBuilding">-</div>
                </div>

                <div class="mb-3">
                    <label class="small text-muted fw-semibold">Keperluan / Acara:</label>
                    <div class="p-3 bg-light rounded-3 fw-bold text-dark" id="modalPurpose">-</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Penyelenggara:</label>
                        <div class="fw-semibold text-dark" id="modalBorrower">-</div>
                        <div class="small text-muted" id="modalOrganization">-</div>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-semibold">Waktu Kegiatan:</label>
                        <div class="fw-bold text-success" id="modalTime">-</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="<?= BASE_URL ?>/facilities.php" class="btn btn-primary-usu btn-sm">
                    <i class="bi bi-calendar-plus"></i> Booking Fasilitas Lain
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('fullcalendar-view');
    const facilityFilter = <?= (int)$facilityId ?>;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu',
            day: 'Hari',
            list: 'Daftar'
        },
        locale: 'id',
        events: '<?= BASE_URL ?>/api/calendar_events.php?facility_id=' + facilityFilter,
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            const props = info.event.extendedProps;
            
            document.getElementById('modalTitle').textContent = props.facility_name;
            document.getElementById('modalBookingCode').textContent = props.booking_code;
            document.getElementById('modalFacilityName').textContent = props.facility_name;
            document.getElementById('modalBuilding').textContent = props.building;
            document.getElementById('modalPurpose').textContent = props.purpose;
            document.getElementById('modalBorrower').textContent = props.borrower;
            document.getElementById('modalOrganization').textContent = props.organization;
            document.getElementById('modalTime').textContent = props.time_formatted;
            
            document.getElementById('modalHeaderBg').style.backgroundColor = info.event.backgroundColor || '#057a55';

            const modal = new bootstrap.Modal(document.getElementById('eventDetailModal'));
            modal.show();
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }
    });

    calendar.render();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
