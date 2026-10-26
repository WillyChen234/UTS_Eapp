<?php
// admin/facilities.php - Kelola Data Fasilitas Kampus USU (CRUD)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();

$pageTitle = "Kelola Fasilitas";
require_once __DIR__ . '/../includes/header.php';

$facilities = $db->query("SELECT * FROM facilities ORDER BY category ASC, name ASC")->fetchAll();
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kelola Fasilitas</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">Manajemen Sarana & Fasilitas Kampus</h1>
                <p class="text-muted small mb-0">Kelola data laboratorium, aula, sarana olahraga, kapasitas, dan tarif retribusi per jam.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/admin/facility_form.php" class="btn btn-primary-usu shadow">
                    <i class="bi bi-plus-circle-fill"></i> Tambah Fasilitas Baru
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Kode & Nama Fasilitas</th>
                        <th>Kategori</th>
                        <th>Gedung / Lokasi</th>
                        <th>Kapasitas</th>
                        <th>Tarif Sewa</th>
                        <th>Status</th>
                        <th class="text-center">Aksi CRUD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facilities)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data fasilitas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($facilities as $f): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['name']) ?>" class="rounded-3 shadow-sm" style="width:70px; height:50px; object-fit:cover;">
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border font-monospace small mb-1"><?= htmlspecialchars($f['code']) ?></span>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($f['name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($f['category']) ?></span>
                                </td>
                                <td>
                                    <div class="small text-muted"><?= htmlspecialchars($f['building']) ?></div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark"><i class="bi bi-people"></i> <?= $f['capacity'] ?> Orang</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-success"><?= rupiah($f['hourly_rate']) ?></span>
                                    <?php if ($f['hourly_rate'] > 0): ?>
                                        <div class="small text-muted">/ jam</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-status-chip <?= $f['status'] == 'available' ? 'badge-status-available' : 'badge-status-maintenance' ?>" style="position:static;">
                                        <?= $f['status'] == 'available' ? '✓ Tersedia' : 'Maintenance' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?= BASE_URL ?>/facility_detail.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Pratinjau" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/facility_form.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ubah / Edit">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/facility_delete.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus fasilitas ini? Semua riwayat booking terkait juga akan terhapus.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
