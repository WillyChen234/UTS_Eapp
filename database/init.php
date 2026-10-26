<?php
// database/init.php
// Script inisialisasi tabel dan sample data otomatis (SQLite & MySQL compatible)

require_once __DIR__ . '/../config/database.php';

function init_database() {
    $pdo = get_db_connection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // 1. Buat Tabel Users
    $sqlUsers = ($driver === 'sqlite') ? "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user', -- 'admin', 'user'
            nim_nip VARCHAR(50),
            department VARCHAR(100),
            phone VARCHAR(30),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    " : "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            nim_nip VARCHAR(50),
            department VARCHAR(100),
            phone VARCHAR(30),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlUsers);

    // 2. Buat Tabel Facilities
    $sqlFacilities = ($driver === 'sqlite') ? "
        CREATE TABLE IF NOT EXISTS facilities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(30) NOT NULL UNIQUE,
            name VARCHAR(200) NOT NULL,
            category VARCHAR(50) NOT NULL, -- 'Laboratorium', 'Aula', 'Lapangan Olahraga', 'Ruang Seminar'
            building VARCHAR(150) NOT NULL,
            capacity INTEGER NOT NULL DEFAULT 50,
            hourly_rate NUMERIC NOT NULL DEFAULT 0,
            description TEXT,
            amenities TEXT,
            image VARCHAR(255),
            status VARCHAR(30) NOT NULL DEFAULT 'available', -- 'available', 'maintenance'
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    " : "
        CREATE TABLE IF NOT EXISTS facilities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NOT NULL UNIQUE,
            name VARCHAR(200) NOT NULL,
            category VARCHAR(50) NOT NULL,
            building VARCHAR(150) NOT NULL,
            capacity INT NOT NULL DEFAULT 50,
            hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
            description TEXT,
            amenities TEXT,
            image VARCHAR(255),
            status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlFacilities);

    // 3. Buat Tabel Bookings
    $sqlBookings = ($driver === 'sqlite') ? "
        CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            booking_code VARCHAR(50) NOT NULL UNIQUE,
            user_id INTEGER NOT NULL,
            facility_id INTEGER NOT NULL,
            booking_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration_hours REAL NOT NULL,
            purpose VARCHAR(255) NOT NULL,
            organization_type VARCHAR(50) NOT NULL DEFAULT 'Mahasiswa',
            attendee_count INTEGER DEFAULT 30,
            additional_facilities TEXT,
            total_amount NUMERIC NOT NULL DEFAULT 0,
            payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid', -- 'unpaid', 'pending_verification', 'paid', 'free_academic'
            payment_method VARCHAR(50) DEFAULT 'qris',
            payment_ref VARCHAR(100),
            payment_proof VARCHAR(255),
            paid_at DATETIME,
            booking_status VARCHAR(30) NOT NULL DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'completed', 'cancelled'
            admin_notes TEXT,
            qr_code_token VARCHAR(100) NOT NULL UNIQUE,
            check_in_status VARCHAR(30) NOT NULL DEFAULT 'not_checked_in', -- 'not_checked_in', 'checked_in'
            checked_in_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
        );
    " : "
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_code VARCHAR(50) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            facility_id INT NOT NULL,
            booking_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration_hours DECIMAL(4,2) NOT NULL,
            purpose VARCHAR(255) NOT NULL,
            organization_type VARCHAR(50) NOT NULL DEFAULT 'Mahasiswa',
            attendee_count INT DEFAULT 30,
            additional_facilities TEXT,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_status ENUM('unpaid', 'pending_verification', 'paid', 'free_academic') NOT NULL DEFAULT 'unpaid',
            payment_method VARCHAR(50) DEFAULT 'qris',
            payment_ref VARCHAR(100),
            payment_proof VARCHAR(255),
            paid_at DATETIME,
            booking_status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
            admin_notes TEXT,
            qr_code_token VARCHAR(100) NOT NULL UNIQUE,
            check_in_status ENUM('not_checked_in', 'checked_in') NOT NULL DEFAULT 'not_checked_in',
            checked_in_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlBookings);

    // 4. Buat Tabel Notifications
    $sqlNotif = ($driver === 'sqlite') ? "
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            booking_id INTEGER,
            channel VARCHAR(20) NOT NULL DEFAULT 'system', -- 'system', 'whatsapp', 'email'
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            recipient VARCHAR(100),
            is_read INTEGER DEFAULT 0,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    " : "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            booking_id INT,
            channel ENUM('system', 'whatsapp', 'email') NOT NULL DEFAULT 'system',
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            recipient VARCHAR(100),
            is_read TINYINT(1) DEFAULT 0,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlNotif);

    // SEEDING DATA JIKA MASIH KOSONG
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUser == 0) {
        // 1. Seed Users
        $users = [
            [
                'name' => 'Dr. Ir. Budi Rahardjo, M.Kom',
                'email' => 'admin@usu.ac.id',
                'password' => password_hash('admin123', PASSWORD_BCRYPT),
                'role' => 'admin',
                'nim_nip' => '198005122005011002',
                'department' => 'Kepala Pengelola Sarpras Vokasi USU',
                'phone' => '081234567890'
            ],
            [
                'name' => 'Muhammad Rizky Pratama',
                'email' => 'mahasiswa@usu.ac.id',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => 'user',
                'nim_nip' => '232406045',
                'department' => 'D3 Teknik Informatika USU',
                'phone' => '082198765432'
            ],
            [
                'name' => 'HIMATIF USU (Himpunan Mahasiswa D3 TI)',
                'email' => 'himatif@usu.ac.id',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => 'user',
                'nim_nip' => '232406001',
                'department' => 'Ormawa D3 TI Vokasi USU',
                'phone' => '081377889900'
            ],
            [
                'name' => 'Dra. Sri Wahyuni, M.Sc',
                'email' => 'dosen@usu.ac.id',
                'password' => password_hash('user123', PASSWORD_BCRYPT),
                'role' => 'user',
                'nim_nip' => '197508242000122001',
                'department' => 'Dosen Tetap Vokasi USU',
                'phone' => '085211223344'
            ]
        ];

        $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, nim_nip, department, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $stmtUser->execute([$u['name'], $u['email'], $u['password'], $u['role'], $u['nim_nip'], $u['department'], $u['phone']]);
        }
    }

    $checkFac = $pdo->query("SELECT COUNT(*) FROM facilities")->fetchColumn();
    if ($checkFac == 0) {
        // 2. Seed Facilities
        $facilities = [
            [
                'code' => 'LAB-TI-01',
                'name' => 'Laboratorium Komputer & Software Engineering D3 TI',
                'category' => 'Laboratorium',
                'building' => 'Gedung Vokasi USU Lantai 2 (Lab A)',
                'capacity' => 45,
                'hourly_rate' => 50000,
                'description' => 'Laboratorium komputer canggih dengan 45 unit PC berspesifikasi tinggi untuk praktikum pemrograman web, jaringan komputer, cloud computing, dan workshop teknologi informasi.',
                'amenities' => '45 Unit PC Core i7 16GB RAM SSD, Dual Gigabit Switch, Mikrotik Routerboard, Proyektor Laser 4K, Smart Interactive Display, 2 Unit AC Daikin 2PK, Whiteboard Magnetik, High Speed WiFi 500 Mbps',
                'image' => 'assets/images/lab_komputer.jpg',
                'status' => 'available'
            ],
            [
                'code' => 'AULA-VK-01',
                'name' => 'Aula Serbaguna Gedung Vokasi USU',
                'category' => 'Aula',
                'building' => 'Gedung Utama Vokasi USU Lantai 1',
                'capacity' => 350,
                'hourly_rate' => 150000,
                'description' => 'Aula megah bertaraf representatif untuk seminar nasional, kuliah umum, pelantikan ormawa, pameran inovasi mahasiswa, serta pertunjukan seni kampus.',
                'amenities' => 'Panggung Teater Kayu Jati, 350 Kursi Duduk Busa, Proyektor Barco 10.000 Lumens, Line Array Sound System JBL, 4 Mic Wireless Shure, AC Sentral 10 PK, Podium Resmi Logo USU, Ruang VIP Transit, Backstage',
                'image' => 'assets/images/aula_vokasi.jpg',
                'status' => 'available'
            ],
            [
                'code' => 'LAP-OR-01',
                'name' => 'Gelanggang Olahraga & Lapangan Futsal Vokasi',
                'category' => 'Lapangan Olahraga',
                'building' => 'Kompleks Olahraga Mahasiswa Vokasi USU',
                'capacity' => 120,
                'hourly_rate' => 75000,
                'description' => 'Lapangan olahraga indoor multifungsi dengan lantai vinyl parquet bersertifikasi, sangat cocok untuk pertandingan futsal, basket 3x3, dan turnamen olahraga antar mahasiswa.',
                'amenities' => 'Lantai Parquet Anti-Slip Standar Nasional, Gawang Futsal & Ring Basket Hidrolik, Lampu Sorot LED Stadium 1500 Watt, Papan Skor Digital LED Wireless, Tribun Penonton 120 Tempat Duduk, Kamar Bilas & Locker Room',
                'image' => 'assets/images/lapangan_olahraga.jpg',
                'status' => 'available'
            ],
            [
                'code' => 'SEM-VK-02',
                'name' => 'Ruang Seminar & Smart Class Vokasi',
                'category' => 'Ruang Seminar',
                'building' => 'Gedung Vokasi USU Lantai 3 (Ruang 305)',
                'capacity' => 80,
                'hourly_rate' => 60000,
                'description' => 'Ruang seminar modern dengan konfigurasi meja konferensi fleksibel, dilengkapi peralatan hybrid video conference untuk sidang akhir, workshop skripsi, dan pelatihan sertifikasi.',
                'amenities' => 'Smart Board 85 Inch Touchscreen, Video Conference Polycom 4K, 80 Kursi Kuliah Ergonomis, Soundbar Audio Premium, 2 Unit AC Daikin 2.5PK, Coffee Corner & Dispenser',
                'image' => 'assets/images/lab_komputer.jpg',
                'status' => 'available'
            ]
        ];

        $stmtFac = $pdo->prepare("INSERT INTO facilities (code, name, category, building, capacity, hourly_rate, description, amenities, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($facilities as $f) {
            $stmtFac->execute([
                $f['code'], $f['name'], $f['category'], $f['building'], $f['capacity'],
                $f['hourly_rate'], $f['description'], $f['amenities'], $f['image'], $f['status']
            ]);
        }
    }

    $checkBk = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    if ($checkBk == 0) {
        // 3. Seed Realistic Bookings
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $nextWeek = date('Y-m-d', strtotime('+3 days'));

        $bookings = [
            [
                'booking_code' => 'USU-BK-202610-001',
                'user_id' => 3, // HIMATIF
                'facility_id' => 1, // Lab Komputer
                'booking_date' => $today,
                'start_time' => '09:00',
                'end_time' => '13:00',
                'duration_hours' => 4.0,
                'purpose' => 'Bootcamp Fullstack Web & Cloud Architecture HIMATIF 2026',
                'organization_type' => 'Himpunan / Ormawa',
                'attendee_count' => 42,
                'additional_facilities' => 'Sound System, Proyektor Tambahan, Mikrofon Wireless',
                'total_amount' => 200000,
                'payment_status' => 'paid',
                'payment_method' => 'qris',
                'payment_ref' => 'QRIS-USU-8829104',
                'payment_proof' => 'uploads/payment_proofs/sample_qris.png',
                'paid_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'booking_status' => 'approved',
                'admin_notes' => 'Disetujui. Harap menjaga kebersihan dan perangkat lab setelah kegiatan.',
                'qr_code_token' => 'QR-USU-BK202610001-TOKENXYZ',
                'check_in_status' => 'checked_in',
                'checked_in_at' => date('Y-m-d 08:50:00')
            ],
            [
                'booking_code' => 'USU-BK-202610-002',
                'user_id' => 2, // Mahasiswa Rizky
                'facility_id' => 2, // Aula Vokasi
                'booking_date' => $tomorrow,
                'start_time' => '13:00',
                'end_time' => '17:00',
                'duration_hours' => 4.0,
                'purpose' => 'Seminar Karir IT & Peluang Kerja Global Lulusan D3 TI',
                'organization_type' => 'Mahasiswa / Panitia',
                'attendee_count' => 180,
                'additional_facilities' => 'Lighting Panggung, Podium Resmi, Sound System 10.000 Watt',
                'total_amount' => 600000,
                'payment_status' => 'pending_verification',
                'payment_method' => 'bni_va',
                'payment_ref' => 'VA-BNI-98823240604501',
                'payment_proof' => 'uploads/payment_proofs/sample_transfer.png',
                'paid_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                'booking_status' => 'pending',
                'admin_notes' => 'Menunggu verifikasi bukti transfer oleh Bendahara Sarpras.',
                'qr_code_token' => 'QR-USU-BK202610002-TOKENABC',
                'check_in_status' => 'not_checked_in',
                'checked_in_at' => null
            ],
            [
                'booking_code' => 'USU-BK-202610-003',
                'user_id' => 3, // HIMATIF
                'facility_id' => 3, // Lapangan Futsal
                'booking_date' => $nextWeek,
                'start_time' => '16:00',
                'end_time' => '19:00',
                'duration_hours' => 3.0,
                'purpose' => 'Turnamen Futsal Keakraban Mahasiswa & Alumni D3 TI USU',
                'organization_type' => 'Himpunan / Ormawa',
                'attendee_count' => 60,
                'additional_facilities' => 'Papan Skor Digital, Lampu Sorot Tambahan',
                'total_amount' => 225000,
                'payment_status' => 'paid',
                'payment_method' => 'gopay',
                'payment_ref' => 'EWALLET-GOPAY-991204',
                'payment_proof' => 'uploads/payment_proofs/sample_ewallet.png',
                'paid_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'booking_status' => 'approved',
                'admin_notes' => 'Disetujui. Patuhi tata tertib gelanggang olahraga.',
                'qr_code_token' => 'QR-USU-BK202610003-TOKENDEF',
                'check_in_status' => 'not_checked_in',
                'checked_in_at' => null
            ],
            [
                'booking_code' => 'USU-BK-202610-004',
                'user_id' => 4, // Dosen
                'facility_id' => 4, // Ruang Seminar
                'booking_date' => $yesterday,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'duration_hours' => 3.0,
                'purpose' => 'Kuliah Tamu Praktisi Industri Perbankan Syariah',
                'organization_type' => 'Akademik / Dosen',
                'attendee_count' => 50,
                'additional_facilities' => 'Polycom Video Conference, Mic Meja',
                'total_amount' => 0,
                'payment_status' => 'free_academic',
                'payment_method' => 'free_academic',
                'payment_ref' => 'SURAT-TUGAS-DEKAN-889',
                'payment_proof' => null,
                'paid_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'booking_status' => 'completed',
                'admin_notes' => 'Kegiatan akademik resmi prodi selesai dengan sukses.',
                'qr_code_token' => 'QR-USU-BK202610004-TOKENOLD',
                'check_in_status' => 'checked_in',
                'checked_in_at' => date('Y-m-d 08:45:00', strtotime('-1 day'))
            ]
        ];

        $stmtBk = $pdo->prepare("
            INSERT INTO bookings (
                booking_code, user_id, facility_id, booking_date, start_time, end_time,
                duration_hours, purpose, organization_type, attendee_count, additional_facilities,
                total_amount, payment_status, payment_method, payment_ref, payment_proof,
                paid_at, booking_status, admin_notes, qr_code_token, check_in_status, checked_in_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($bookings as $b) {
            $stmtBk->execute([
                $b['booking_code'], $b['user_id'], $b['facility_id'], $b['booking_date'], $b['start_time'], $b['end_time'],
                $b['duration_hours'], $b['purpose'], $b['organization_type'], $b['attendee_count'], $b['additional_facilities'],
                $b['total_amount'], $b['payment_status'], $b['payment_method'], $b['payment_ref'], $b['payment_proof'],
                $b['paid_at'], $b['booking_status'], $b['admin_notes'], $b['qr_code_token'], $b['check_in_status'], $b['checked_in_at']
            ]);
        }
    }

    // Seed dummy notification
    $checkNotif = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    if ($checkNotif == 0) {
        $notifs = [
            [
                'user_id' => 3,
                'booking_id' => 1,
                'channel' => 'whatsapp',
                'title' => 'Tiket E-Booking Terbit - USU SmartFacility',
                'message' => 'Halo HIMATIF USU! Booking Lab Komputer & Jaringan D3 TI (USU-BK-202610-001) telah DISETUJUI oleh Admin Sarpras. Tunjukkan QR Code pada tiket saat masuk.',
                'recipient' => '081377889900'
            ],
            [
                'user_id' => 2,
                'booking_id' => 2,
                'channel' => 'email',
                'title' => 'Instruksi Pembayaran E-Booking USU',
                'message' => 'Yth. Muhammad Rizky Pratama, permohonan booking Aula Vokasi USU (USU-BK-202610-002) telah tercatat. Silakan lakukan pembayaran via BNI Virtual Account 98823240604501 sebesar Rp 600.000.',
                'recipient' => 'mahasiswa@usu.ac.id'
            ]
        ];
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, booking_id, channel, title, message, recipient) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($notifs as $n) {
            $stmtNotif->execute([$n['user_id'], $n['booking_id'], $n['channel'], $n['title'], $n['message'], $n['recipient']]);
        }
    }
}

// Auto init on include
init_database();
