<?php
// register.php - Pendaftaran Akun Mahasiswa / Dosen / Ormawa USU
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_guard.php';

if (is_logged_in()) {
    redirect_to(BASE_URL . "/index.php");
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nimNip = trim($_POST['nim_nip'] ?? '');
    $department = trim($_POST['department'] ?? 'D3 Teknik Informatika');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Kolom Nama, Email, dan Kata Sandi wajib diisi.";
    } else {
        // Cek email duplikat
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmtIns = $db->prepare("
                INSERT INTO users (name, email, password, role, nim_nip, department, phone, created_at)
                VALUES (?, ?, ?, 'user', ?, ?, ?, " . ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now', 'localtime')" : "NOW()") . ")
            ");
            $stmtIns->execute([$name, $email, $hashed, $nimNip, $department, $phone]);

            set_flash('success', 'Pendaftaran akun berhasil! Silakan masuk dengan akun Anda.');
            redirect_to(BASE_URL . "/login.php");
        }
    }
}

$pageTitle = "Daftar Akun Baru";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 my-md-3">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="p-4 bg-gradient text-white text-center" style="background: linear-gradient(135deg, #03543f 0%, #057a55 100%);">
                    <h4 class="fw-bold mb-1">Daftar Akun USU SmartFacility</h4>
                    <p class="small opacity-90 mb-0">Registrasi Sivitas Akademika D3 TI & Vokasi USU</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/register.php">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nama Lengkap / Nama Organisasi <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso / HIMATIF USU" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">NIM / NIP / Kode Unit</label>
                                <input type="text" name="nim_nip" class="form-control" placeholder="Contoh: 232406045">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Program Studi / Unit</label>
                                <input type="text" name="department" class="form-control" value="D3 Teknik Informatika USU" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Email Akun USU <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="nama@usu.ac.id" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Nomor WhatsApp Aktif <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" placeholder="081234567890" required>
                            <div class="form-text small" style="font-size:0.75rem;">Digunakan untuk menerima notifikasi simulasi tiket E-Booking.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-dark">Kata Sandi (Password) <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>

                        <button type="submit" class="btn btn-primary-usu w-100 justify-content-center py-2 shadow">
                            <i class="bi bi-person-check-fill"></i> Buat Akun Sekarang
                        </button>
                    </form>

                    <div class="text-center mt-4 small text-muted">
                        Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" class="fw-bold text-success">Masuk di sini</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
