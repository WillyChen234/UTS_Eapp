-- ========================================================
-- TUGAS PROJECT UTS - MATA KULIAH E-APPLICATION
-- Program Studi: D3 Teknik Informatika, Fak. Vokasi, Universitas Sumatera Utara (USU)
-- Judul 2: E-Booking & E-Payment Fasilitas Kampus (Lab, Aula, Lapangan)
-- Nama Sistem: USU SmartFacility (Sistem E-Booking & Retribusi Fasilitas Terintegrasi)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `usu_smartfacility` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `usu_smartfacility`;

-- --------------------------------------------------------
-- 1. Struktur Tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `facilities`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL COMMENT 'Nama Lengkap Mahasiswa / Dosen / Admin',
  `email` VARCHAR(150) NOT NULL UNIQUE COMMENT 'Email resmi USU / Pengguna',
  `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt Password Hashed',
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user' COMMENT 'Peran dalam sistem',
  `nim_nip` VARCHAR(50) DEFAULT NULL COMMENT 'NIM Mahasiswa atau NIP Dosen/Admin',
  `department` VARCHAR(100) DEFAULT NULL COMMENT 'Program Studi / Departemen / Unit Kerja',
  `phone` VARCHAR(30) DEFAULT NULL COMMENT 'Nomor WhatsApp untuk notifikasi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Struktur Tabel `facilities`
-- --------------------------------------------------------
CREATE TABLE `facilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Kode Unik Fasilitas (e.g. LAB-TI-01)',
  `name` VARCHAR(200) NOT NULL COMMENT 'Nama Fasilitas Kampus',
  `category` VARCHAR(50) NOT NULL COMMENT 'Laboratorium, Aula, Lapangan Olahraga, Ruang Seminar',
  `building` VARCHAR(150) NOT NULL COMMENT 'Lokasi Gedung & Lantai',
  `capacity` INT NOT NULL DEFAULT 50 COMMENT 'Kapasitas maksimal orang',
  `hourly_rate` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Tarif sewa per jam (Rp)',
  `description` TEXT DEFAULT NULL COMMENT 'Deskripsi detail fasilitas',
  `amenities` TEXT DEFAULT NULL COMMENT 'Daftar fasilitas penunjang & spesifikasi',
  `image` VARCHAR(255) DEFAULT NULL COMMENT 'Path foto representatif fasilitas',
  `status` ENUM('available', 'maintenance') NOT NULL DEFAULT 'available' COMMENT 'Ketersediaan operasional',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Struktur Tabel `bookings` (Transaksi Utama E-Application)
-- --------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nomor Transaksi Unik (e.g. USU-BK-202610-001)',
  `user_id` INT NOT NULL COMMENT 'Relasi ke peminjam (users.id)',
  `facility_id` INT NOT NULL COMMENT 'Relasi ke fasilitas (facilities.id)',
  `booking_date` DATE NOT NULL COMMENT 'Tanggal pemakaian',
  `start_time` TIME NOT NULL COMMENT 'Jam mulai kegiatan',
  `end_time` TIME NOT NULL COMMENT 'Jam selesai kegiatan',
  `duration_hours` DECIMAL(4,2) NOT NULL COMMENT 'Total durasi dalam jam',
  `purpose` VARCHAR(255) NOT NULL COMMENT 'Keperluan / Nama Acara',
  `organization_type` VARCHAR(50) NOT NULL DEFAULT 'Mahasiswa' COMMENT 'Akademik, Ormawa, Mahasiswa, Eksternal',
  `attendee_count` INT DEFAULT 30 COMMENT 'Estimasi jumlah peserta',
  `additional_facilities` TEXT DEFAULT NULL COMMENT 'Permintaan sound, proyektor, dll',
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Total nominal pembayaran sewa',
  `payment_status` ENUM('unpaid', 'pending_verification', 'paid', 'free_academic') NOT NULL DEFAULT 'unpaid',
  `payment_method` VARCHAR(50) DEFAULT 'qris' COMMENT 'qris, bni_va, mandiri_va, bri_va, gopay, transfer_manual',
  `payment_ref` VARCHAR(100) DEFAULT NULL COMMENT 'Kode referensi transaksi pembayaran',
  `payment_proof` VARCHAR(255) DEFAULT NULL COMMENT 'File bukti transfer (jika manual)',
  `paid_at` DATETIME DEFAULT NULL COMMENT 'Waktu pembayaran dikonfirmasi',
  `booking_status` ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL COMMENT 'Catatan persetujuan / alasan penolakan admin',
  `qr_code_token` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Token QR Code untuk verifikasi tiket masuk',
  `check_in_status` ENUM('not_checked_in', 'checked_in') NOT NULL DEFAULT 'not_checked_in' COMMENT 'Status kehadiran di lokasi',
  `checked_in_at` DATETIME DEFAULT NULL COMMENT 'Waktu scan tiket QR code di pintu masuk',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Struktur Tabel `notifications` (Simulasi Notifikasi WA / Email)
-- --------------------------------------------------------
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `booking_id` INT DEFAULT NULL,
  `channel` ENUM('system', 'whatsapp', 'email') NOT NULL DEFAULT 'system',
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `recipient` VARCHAR(100) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DATA SEED (Pengguna Awal)
-- Password Default:
-- Admin: admin123
-- User / Mahasiswa: user123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `nim_nip`, `department`, `phone`, `created_at`) VALUES
(1, 'Dr. Ir. Budi Rahardjo, M.Kom', 'admin@usu.ac.id', '$2y$10$wE8wY5j3pXFjV1z.R6vK3OCu3sM9hK9uH0T6G8LzXmE1vBq6S6h5y', 'admin', '198005122005011002', 'Kepala Pengelola Sarpras Vokasi USU', '081234567890', NOW()),
(2, 'Muhammad Rizky Pratama', 'mahasiswa@usu.ac.id', '$2y$10$tZz09zT/6l.iXlB5lJ8c2O4W5R5n2M1zV0c3D7a5F8g9H0j1K2l3m', 'user', '232406045', 'D3 Teknik Informatika USU', '082198765432', NOW()),
(3, 'HIMATIF USU (Ketua: Kevin Sinaga)', 'himatif@usu.ac.id', '$2y$10$tZz09zT/6l.iXlB5lJ8c2O4W5R5n2M1zV0c3D7a5F8g9H0j1K2l3m', 'user', '232406001', 'Ormawa D3 TI Vokasi USU', '081377889900', NOW()),
(4, 'Dra. Sri Wahyuni, M.Sc', 'dosen@usu.ac.id', '$2y$10$tZz09zT/6l.iXlB5lJ8c2O4W5R5n2M1zV0c3D7a5F8g9H0j1K2l3m', 'user', '197508242000122001', 'Dosen Tetap Vokasi USU', '085211223344', NOW());

-- --------------------------------------------------------
-- DATA SEED (Daftar Fasilitas Kampus)
-- --------------------------------------------------------
INSERT INTO `facilities` (`id`, `code`, `name`, `category`, `building`, `capacity`, `hourly_rate`, `description`, `amenities`, `image`, `status`, `created_at`) VALUES
(1, 'LAB-TI-01', 'Laboratorium Komputer & Software Engineering D3 TI', 'Laboratorium', 'Gedung Vokasi USU Lantai 2 (Lab A)', 45, 50000.00, 'Laboratorium komputer canggih dengan 45 unit PC berspesifikasi tinggi untuk praktikum pemrograman web, jaringan komputer, cloud computing, dan workshop teknologi informasi.', '45 Unit PC Core i7 16GB RAM SSD, Dual Gigabit Switch, Mikrotik Routerboard, Proyektor Laser 4K, Smart Interactive Display, 2 Unit AC Daikin 2PK, Whiteboard Magnetik, High Speed WiFi 500 Mbps', 'assets/images/lab_komputer.jpg', 'available', NOW()),
(2, 'AULA-VK-01', 'Aula Serbaguna Gedung Vokasi USU', 'Aula', 'Gedung Utama Vokasi USU Lantai 1', 350, 150000.00, 'Aula megah bertaraf representatif untuk seminar nasional, kuliah umum, pelantikan ormawa, pameran inovasi mahasiswa, serta pertunjukan seni kampus.', 'Panggung Teater Kayu Jati, 350 Kursi Duduk Busa, Proyektor Barco 10.000 Lumens, Line Array Sound System JBL, 4 Mic Wireless Shure, AC Sentral 10 PK, Podium Resmi Logo USU, Ruang VIP Transit, Backstage', 'assets/images/aula_vokasi.jpg', 'available', NOW()),
(3, 'LAP-OR-01', 'Gelanggang Olahraga & Lapangan Futsal Vokasi', 'Lapangan Olahraga', 'Kompleks Olahraga Mahasiswa Vokasi USU', 120, 75000.00, 'Lapangan olahraga indoor multifungsi dengan lantai vinyl parquet bersertifikasi, sangat cocok untuk pertandingan futsal, basket 3x3, dan turnamen olahraga antar mahasiswa.', 'Lantai Parquet Anti-Slip Standar Nasional, Gawang Futsal & Ring Basket Hidrolik, Lampu Sorot LED Stadium 1500 Watt, Papan Skor Digital LED Wireless, Tribun Penonton 120 Tempat Duduk, Kamar Bilas & Locker Room', 'assets/images/lapangan_olahraga.jpg', 'available', NOW()),
(4, 'SEM-VK-02', 'Ruang Seminar & Smart Class Vokasi', 'Ruang Seminar', 'Gedung Vokasi USU Lantai 3 (Ruang 305)', 80, 60000.00, 'Ruang seminar modern dengan konfigurasi meja konferensi fleksibel, dilengkapi peralatan hybrid video conference untuk sidang akhir, workshop skripsi, dan pelatihan sertifikasi.', 'Smart Board 85 Inch Touchscreen, Video Conference Polycom 4K, 80 Kursi Kuliah Ergonomis, Soundbar Audio Premium, 2 Unit AC Daikin 2.5PK, Coffee Corner & Dispenser', 'assets/images/lab_komputer.jpg', 'available', NOW());

-- --------------------------------------------------------
-- DATA SEED (Sample Transaksi E-Booking)
-- --------------------------------------------------------
INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `facility_id`, `booking_date`, `start_time`, `end_time`, `duration_hours`, `purpose`, `organization_type`, `attendee_count`, `additional_facilities`, `total_amount`, `payment_status`, `payment_method`, `payment_ref`, `paid_at`, `booking_status`, `admin_notes`, `qr_code_token`, `check_in_status`, `checked_in_at`, `created_at`) VALUES
(1, 'USU-BK-202610-001', 3, 1, CURDATE(), '09:00:00', '13:00:00', 4.00, 'Bootcamp Fullstack Web & Cloud Architecture HIMATIF 2026', 'Himpunan / Ormawa', 42, 'Sound System, Proyektor Tambahan, Mikrofon Wireless', 200000.00, 'paid', 'qris', 'QRIS-USU-8829104', NOW(), 'approved', 'Disetujui. Harap menjaga kebersihan dan perangkat lab setelah kegiatan.', 'QR-USU-BK202610001-TOKENXYZ', 'checked_in', NOW(), NOW()),
(2, 'USU-BK-202610-002', 2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '17:00:00', 4.00, 'Seminar Karir IT & Peluang Kerja Global Lulusan D3 TI', 'Mahasiswa / Panitia', 180, 'Lighting Panggung, Podium Resmi, Sound System 10.000 Watt', 600000.00, 'pending_verification', 'bni_va', 'VA-BNI-98823240604501', NOW(), 'pending', 'Menunggu verifikasi bukti transfer oleh Bendahara Sarpras.', 'QR-USU-BK202610002-TOKENABC', 'not_checked_in', NULL, NOW()),
(3, 'USU-BK-202610-003', 3, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '16:00:00', '19:00:00', 3.00, 'Turnamen Futsal Keakraban Mahasiswa & Alumni D3 TI USU', 'Himpunan / Ormawa', 60, 'Papan Skor Digital, Lampu Sorot Tambahan', 225000.00, 'paid', 'gopay', 'EWALLET-GOPAY-991204', NOW(), 'approved', 'Disetujui. Patuhi tata tertib gelanggang olahraga.', 'QR-USU-BK202610003-TOKENDEF', 'not_checked_in', NULL, NOW());

-- --------------------------------------------------------
-- DATA SEED (Sample Notifikasi)
-- --------------------------------------------------------
INSERT INTO `notifications` (`id`, `user_id`, `booking_id`, `channel`, `title`, `message`, `recipient`, `is_read`, `sent_at`) VALUES
(1, 3, 1, 'whatsapp', 'Tiket E-Booking Terbit - USU SmartFacility', 'Halo HIMATIF USU! Booking Lab Komputer & Jaringan D3 TI (USU-BK-202610-001) telah DISETUJUI oleh Admin Sarpras. Tunjukkan QR Code pada tiket saat masuk.', '081377889900', 1, NOW()),
(2, 2, 2, 'email', 'Instruksi Pembayaran E-Booking USU', 'Yth. Muhammad Rizky Pratama, permohonan booking Aula Vokasi USU (USU-BK-202610-002) telah tercatat. Silakan lakukan pembayaran via BNI Virtual Account 98823240604501 sebesar Rp 600.000.', 'mahasiswa@usu.ac.id', 0, NOW());

COMMIT;
