<?php
// includes/footer.php
?>
    <!-- Footer Kampus -->
    <footer class="mt-auto bg-white border-top pt-5 pb-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-logo-badge" style="width:36px; height:36px; font-size:1rem;">
                            <i class="bi bi-building-check"></i>
                        </div>
                        <h5 class="mb-0 fw-bold"><?= APP_NAME ?></h5>
                    </div>
                    <p class="text-muted small">
                        Sistem Informasi E-Booking & E-Payment Fasilitas Kampus Terintegrasi guna pencegahan bentrok jadwal pemakaian dan transparansi pengelolaan sarana prasarana.
                    </p>
                    <div class="text-muted small">
                        <strong>Program Studi:</strong> D3 Teknik Informatika<br>
                        <strong>Fakultas:</strong> Vokasi Universitas Sumatera Utara<br>
                        <strong>Mata Kuliah:</strong> E-Application (Tugas UTS Take-Home)
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3 text-dark">Navigasi Utama</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= BASE_URL ?>/index.php" class="text-secondary text-decoration-none">Beranda</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/facilities.php" class="text-secondary text-decoration-none">Katalog Fasilitas</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/calendar.php" class="text-secondary text-decoration-none">Kalender Ketersediaan</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/verify.php" class="text-secondary text-decoration-none">Verifikasi E-Ticket QR</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/login.php" class="text-secondary text-decoration-none">Login Multi-Role</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3 text-dark">Kategori Fasilitas</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><span class="badge bg-success-subtle text-success me-1">Lab</span> Laboratorium Komputer & Jaringan</li>
                        <li class="mb-2"><span class="badge bg-warning-subtle text-warning-emphasis me-1">Aula</span> Aula Serbaguna Gedung Vokasi</li>
                        <li class="mb-2"><span class="badge bg-primary-subtle text-primary me-1">Sport</span> Lapangan Futsal & Gelanggang</li>
                        <li class="mb-2"><span class="badge bg-info-subtle text-info-emphasis me-1">Ruang</span> Ruang Seminar & Smart Class</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3 text-dark">Bantuan & Kontak Sarpras</h6>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-geo-alt text-success me-2"></i> Gedung Dekanat Fakultas Vokasi USU, Jl. Alumni No. 9, Medan
                    </p>
                    <p class="small text-muted mb-2">
                        <i class="bi bi-whatsapp text-success me-2"></i> Layanan Sarpras: 0812-3456-7890
                    </p>
                    <p class="small text-muted mb-3">
                        <i class="bi bi-envelope text-success me-2"></i> sarpras.vokasi@usu.ac.id
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-secondary border p-2"><i class="bi bi-shield-check text-success"></i> Transaksi Elektronik Aman</span>
                        <span class="badge bg-light text-secondary border p-2"><i class="bi bi-qr-code text-primary"></i> QR Code Terverifikasi</span>
                    </div>
                </div>
            </div>

            <hr class="my-4 text-muted opacity-25">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong>. D3 Teknik Informatika - Fakultas Vokasi USU.
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-success-subtle text-success">UTS E-Application</span>
                    <span class="badge bg-secondary-subtle text-secondary ms-1">Semester Ganjil 2025/2026</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- QRCode JS -->
    <script src="<?= BASE_URL ?>/assets/js/qrcode.min.js"></script>

</body>
</html>
