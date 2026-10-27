# PROPOSAL PROJECT UTS E-APPLICATION
### Program Studi D3 Teknik Informatika - Fakultas Vokasi Universitas Sumatera Utara

---

| Komponen Proposal | Rincian / Deskripsi Lengkap |
|:---|:---|
| **Judul Project yang Dipilih** | **Judul 2: E-Booking & E-Payment Fasilitas Kampus (Lab, Aula, Lapangan)**<br>*(Nama Aplikasi: **USU SmartFacility** - Sistem Informasi Reservasi & Transaksi Retribusi Sarpras Terpadu)* |
| **Nama Anggota Kelompok** | 1. **WillyChen234 / William Tanwijaya** (NIM: `[Isi NIM Anda]` / Ketua)<br>*(Kelas: D3 Teknik Informatika - Reguler A / Kelompok Mandiri)* |
| **Latar Belakang (3-4 kalimat)** | Pengelolaan sarana dan prasarana (sarpras) di Fakultas Vokasi USU selama ini masih menggunakan pencatatan manual melalui buku agenda dan formulir fisik, yang kerap menimbulkan risiko bentrok jadwal pemakaian (*double-booking*) antara perkuliahan, praktikum, dan kegiatan organisasi mahasiswa (Ormawa). Selain itu, proses peminjaman berbayar maupun bebas retribusi akademik belum didukung transparansi transaksi elektronik, sehingga peminjam harus mondar-mandir mengurus permohonan ke birokrasi kampus. Hadirnya platform web **USU SmartFacility** mentransformasikan proses ini menjadi otomatis melalui deteksi bentrok jadwal real-time, simulasi pembayaran digital (QRIS & Virtual Account BNI), serta penerbitan *E-Ticket* resmi ber-QR Code untuk verifikasi presensi *check-in* di pintu masuk fasilitas. |
| **Tujuan Aplikasi** | 1. Mempermudah mahasiswa, dosen, dan ormawa dalam mengecek jadwal dan mengajukan reservasi sarpras kampus secara online 24/7.<br>2. Meniadakan insiden bentrok jadwal pemakaian ruangan dengan algoritma *conflict check* otomatis berbasis irisan waktu.<br>3. Menerapkan transaksi elektronik modern melalui integrasi simulasi multi-metode pembayaran (QRIS Standar Nasional, BNI Virtual Account, dan E-Wallet).<br>4. Menyediakan sistem tiket elektronik (E-Ticket) ber-QR Code yang dapat dipindai oleh satpam/teknisi lab untuk verifikasi kehadiran.<br>5. Meningkatkan transparansi dan akuntabilitas pelaporan pemakaian dan retribusi kas sarpras bagi manajemen Fakultas Vokasi USU. |
| **Fitur Utama** | 1. **Login Multi-Role**: Hak akses terpisah untuk *Admin Sarpras Vokasi USU* dan *User Mahasiswa/Dosen/Ormawa*, dilengkapi fitur *1-Click Demo Login* untuk mempermudah demonstrasi evaluasi UTS.<br>2. **Katalog & Kalender Ketersediaan Real-Time**: Tampilan kalender interaktif (FullCalendar.js) dengan pengelompokan warna kategori (*Lab, Aula, Lapangan, Ruang Seminar*).<br>3. **Validasi Anti-Bentrok Slot Jam**: Pengecekan otomatis AJAX untuk memastikan jam permohonan tidak bersinggungan dengan agenda yang sudah terdaftar.<br>4. **Payment Gateway Simulator**: Simulasi checkout digital (QRIS Nasional dengan scan barcode, BNI VA dengan generator nomor rekening virtual, dan upload bukti transfer).<br>5. **E-Ticket Ber-QR Code & Verifikasi Mandiri**: Penerbitan tiket digital berstempel sah BMN USU dan halaman scanner publik (`/verify.php`) untuk check-in petugas di lokasi.<br>6. **Laporan & Rekapitulasi Kas**: Dashboard analitik dengan grafik Chart.js, filter tanggal sewa, ekspor spreadsheet CSV Excel, dan cetak PDF dengan Kop Surat Resmi Universitas Sumatera Utara. |
| **Teknologi yang Digunakan** | - **Backend**: PHP 8 (Arsitektur Native Modular Terstruktur, PDO Multi-Database Driver).<br>- **Database**: Dual-driver (SQLite Zero-Config siap jalan langsung & MySQL/MariaDB dengan skema SQL relasional).<br>- **Frontend & Desain**: HTML5 Semantic, CSS3 Kustom (Harmonisasi Hijau & Kuning Emas Vokasi USU), Bootstrap 5.3, Bootstrap Icons.<br>- **Pustaka JavaScript**: FullCalendar.js v6 (Kalender Jadwal), Chart.js v4 (Visualisasi KPI), QRCode.js (Generator QR Tiket Mandiri).<br>- **Layanan Simulasi**: Mock Midtrans/QRIS Simulator, Virtual Account Generator, WhatsApp & Email Notification Dispatcher. |

<br><br>

<table style="width: 100%; border: none;">
  <tr style="border: none;">
    <td style="width: 50%; border: none;"></td>
    <td style="width: 50%; border: none; text-align: center;">
      Medan, Oktober 2026<br><br>
      <strong>Mengetahui,</strong><br>
      Dosen Pengampu E-Application<br><br><br><br><br>
      ( ___________________________________ )
    </td>
  </tr>
</table>
