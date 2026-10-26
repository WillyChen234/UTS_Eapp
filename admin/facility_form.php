<?php
// admin/facility_form.php - Form Tambah & Edit Fasilitas Kampus USU
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_guard.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$facility = null;

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM facilities WHERE id = ?");
    $stmt->execute([$id]);
    $facility = $stmt->fetch();
    if (!$facility) {
        set_flash('danger', 'Fasilitas tidak ditemukan.');
        redirect_to(BASE_URL . "/admin/facilities.php");
    }
}

$isEdit = !empty($facility);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Laboratorium');
    $building = trim($_POST['building'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 50);
    $hourlyRate = (float)($_POST['hourly_rate'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $amenities = trim($_POST['amenities'] ?? '');
    $status = trim($_POST['status'] ?? 'available');

    $imagePath = $facility['image'] ?? 'assets/images/lab_komputer.jpg';

    // Handle Image Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['image_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newName = 'fac_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
            $targetDir = __DIR__ . '/../uploads/facilities';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $dest = $targetDir . '/' . $newName;
            if (move_uploaded_file($tmp, $dest)) {
                $imagePath = 'uploads/facilities/' . $newName;
            }
        }
    }

    if ($isEdit) {
        $sqlUp = "
            UPDATE facilities
            SET code = ?, name = ?, category = ?, building = ?, capacity = ?,
                hourly_rate = ?, description = ?, amenities = ?, image = ?, status = ?
            WHERE id = ?
        ";
        $stmtUp = $db->prepare($sqlUp);
        $stmtUp->execute([$code, $name, $category, $building, $capacity, $hourlyRate, $description, $amenities, $imagePath, $status, $id]);
        set_flash('success', "Data fasilitas '{$name}' berhasil diperbarui.");
    } else {
        $sqlIns = "
            INSERT INTO facilities (code, name, category, building, capacity, hourly_rate, description, amenities, image, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " . ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now', 'localtime')" : "NOW()") . ")
        ";
        $stmtIns = $db->prepare($sqlIns);
        $stmtIns->execute([$code, $name, $category, $building, $capacity, $hourlyRate, $description, $amenities, $imagePath, $status]);
        set_flash('success', "Fasilitas baru '{$name}' berhasil ditambahkan ke katalog!");
    }

    redirect_to(BASE_URL . "/admin/facilities.php");
}

$pageTitle = $isEdit ? 'Edit Fasilitas' : 'Tambah Fasilitas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/facilities.php">Fasilitas</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' ?></li>
            </ol>
        </nav>
        <h1 class="h2 fw-bold text-dark mb-0"><?= $isEdit ? 'Edit Data Fasilitas' : 'Tambah Fasilitas Baru Kampus USU' ?></h1>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Kode Fasilitas <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="Contoh: LAB-TI-02" value="<?= htmlspecialchars($facility['code'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nama Fasilitas <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Laboratorium Jaringan & Cyber Security" value="<?= htmlspecialchars($facility['name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kategori Fasilitas <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="Laboratorium" <?= ($facility['category'] ?? '') === 'Laboratorium' ? 'selected' : '' ?>>Laboratorium</option>
                                <option value="Aula" <?= ($facility['category'] ?? '') === 'Aula' ? 'selected' : '' ?>>Aula</option>
                                <option value="Lapangan Olahraga" <?= ($facility['category'] ?? '') === 'Lapangan Olahraga' ? 'selected' : '' ?>>Lapangan Olahraga</option>
                                <option value="Ruang Seminar" <?= ($facility['category'] ?? '') === 'Ruang Seminar' ? 'selected' : '' ?>>Ruang Seminar</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lokasi Gedung / Lantai <span class="text-danger">*</span></label>
                            <input type="text" name="building" class="form-control" placeholder="Contoh: Gedung Vokasi USU Lantai 2" value="<?= htmlspecialchars($facility['building'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kapasitas Maksimal (Orang) <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" min="1" max="1000" value="<?= htmlspecialchars($facility['capacity'] ?? 50) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tarif Sewa per Jam (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="hourly_rate" class="form-control" min="0" step="1000" value="<?= htmlspecialchars($facility['hourly_rate'] ?? 0) ?>" required>
                            <div class="form-text small">Ketik 0 jika fasilitas ini gratis tanpa biaya sewa.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi Fasilitas</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Deskripsikan spesifikasi dan peruntukan ruangan..."><?= htmlspecialchars($facility['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fasilitas Penunjang (Pisahkan dengan koma)</label>
                        <input type="text" name="amenities" class="form-control" placeholder="Contoh: AC 2PK, Proyektor 4K, Smart Screen, WiFi 500Mbps" value="<?= htmlspecialchars($facility['amenities'] ?? '') ?>">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status Operasional</label>
                            <select name="status" class="form-select">
                                <option value="available" <?= ($facility['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Tersedia (Siap Digunakan)</option>
                                <option value="maintenance" <?= ($facility['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Dalam Perbaikan (Maintenance)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Foto Fasilitas (JPG/PNG)</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                            <?php if (!empty($facility['image'])): ?>
                                <div class="form-text small">Foto saat ini: <?= htmlspecialchars($facility['image']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= BASE_URL ?>/admin/facilities.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary-usu shadow">
                            <i class="bi bi-save"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Tambahkan Fasilitas' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
