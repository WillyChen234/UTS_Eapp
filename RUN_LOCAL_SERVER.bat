@echo off
title USU SmartFacility - Server Runner
echo ========================================================
echo   USU SmartFacility (E-Booking & E-Payment Fasilitas)
echo   D3 Teknik Informatika - Fakultas Vokasi USU
echo ========================================================
echo Membuka peramban (browser) di http://localhost:8000 ...
start http://localhost:8000
echo Menjalankan PHP local server pada port 8000...
echo Jangan tutup jendela ini selama menggunakan aplikasi.
echo Tekan Ctrl+C untuk menghentikan server.
echo ========================================================
php -S 127.0.0.1:8000 -t "%~dp0"
pause
