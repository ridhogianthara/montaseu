# Montaseu Studio - Web Aplikasi Absensi & Tracking Lokasi GPS

Aplikasi Web Absensi & Tracking Lokasi khusus untuk **Montaseu Studio** (Perusahaan Design Interior). Aplikasi ini dibuat tanpa memerlukan database server (100% Zero Database / JSON Storage) sehingga dapat dijalankan secara langsung dan portable di server mana saja.

---

## 🌟 Fitur Utama

- 🔐 **Dual Role Login (Admin & Karyawan)**:
  - **Admin**: Executive Dashboard, Monitoring Presensi Real-Time, Tracking Peta GPS & Foto Selfie Karyawan, CRUD Data Karyawan, Rekap & Export Laporan ke Excel (.xls & .csv) / Cetak PDF, serta Pengaturan Koordinat GPS Kantor Studio.
  - **Karyawan**: Presensi Selfie Live Snapshot, Automatic GPS Geolocation & Address reverse-geocoding, pilihan kategori lokasi kerja (Studio Office, Client Site Visit, Vendor Workshop, WFH), catatan proyek interior, & riwayat presensi personal.
- 📷 **Selfie Camera Capture**: Ambil foto selfie langsung via kamera browser / hp saat presensi.
- 📍 **GPS Location Tracking & Interactive Maps**: Geolocation tracking presisi dengan konversi alamat otomatis (OpenStreetMap Nominatim) & peta interaktif Leaflet.js.
- 📊 **Export Excel & CSV**: Fitur download laporan presensi dalam format `.xls` terformat dan `.csv`.
- 📁 **Zero Database Architecture**: Semua data tersimpan aman dalam format file JSON (`data/users.json`, `data/attendances.json`, `data/settings.json`).

---

## 🚀 Akses Demo Default

- **URL Portal**: `http://localhost/Montaseu/`
- **Akses Admin Studio**:
  - **Email**: `admin@montaseu.com`
  - **Password**: `admin123`
- **Akses Karyawan**:
  - **Email**: `karyawan@montaseu.com`
  - **Password**: `user123`

---

## 🛠️ Cara Install & Jalankan

1. Clone repositori ini ke folder `htdocs` XAMPP Anda:
   ```bash
   git clone https://github.com/ridhogianthara/montaseu.git
   ```
2. Buka browser dan kunjungi:
   `http://localhost/montaseu/`
3. Aplikasi langsung dapat digunakan tanpa perlu install/import database server!

---

## 📁 Struktur Direktori

```
Montaseu/
├── admin/               # Modul Admin Studio
│   ├── dashboard.php    # Executive Monitoring Dashboard
│   ├── karyawan.php     # Manajemen User (CRUD)
│   ├── rekap.php        # Monitoring Presensi & Map Detail
│   ├── laporan.php      # Laporan & Export Excel/CSV/Print
│   └── pengaturan.php   # Setting GPS Kantor & Jam Operasional
├── employee/            # Modul Karyawan
│   ├── dashboard.php    # Dashboard Karyawan & Jam Digital
│   ├── absen.php        # Form Absensi Kamera & Location GPS
│   └── riwayat.php      # Riwayat Presensi Personal
├── auth/                # Autentikasi Login & Logout
│   ├── login.php
│   └── logout.php
├── assets/              # CSS Design System & JS Engines
│   ├── css/style.css
│   └── js/
│       ├── camera.js
│       ├── location.js
│       └── app.js
├── config/              # Inisialisasi JSON Data Manager
│   └── database.php
├── data/                # File Penyimpanan JSON Data (Zero Database)
│   ├── users.json
│   ├── attendances.json
│   └── settings.json
└── uploads/             # Folder Simpan Foto Selfie
```

---

&copy; <?= date('Y') ?> **Montaseu Studio**. All rights reserved. Interior Design & Architectural Excellence.
