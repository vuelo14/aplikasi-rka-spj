
# Aplikasi Pengelolaan RKA & SPJ (PHP + MySQL)

Aplikasi sederhana untuk mengelola **RKA (Rencana Kerja dan Anggaran)**, **Sub Kegiatan**, **Komponen**, **Rencana Penyerapan Bulanan**, **Realisasi**, dan **unggah SPJ** (kuitansi, nota, dokumentasi) yang mendukung peran **PPTK**, **Bendahara**, dan **Admin**.

> Bahasa: Indonesia | Stack: PHP 8+, MySQL/MariaDB, PDO | Arsitektur: ringan tanpa framework

## Fitur Utama
- **RKA**: CRUD RKA (tahun, nomor, nama kegiatan, pagu total)
- **Sub Kegiatan**: CRUD terhubung ke RKA
- **Komponen RKA**: CRUD (kode/akun, uraian, volume, satuan, harga satuan, total)
- **Rencana Penyerapan**: input rencana bulanan (Jan–Des) per komponen
- **Realisasi**: pencatatan realisasi belanja komponen, terhubung ke SPJ
- **SPJ**: unggah berkas (kuitansi/nota/dokumentasi) dengan validasi ukuran & tipe berkas
- **Dashboard**: ringkasan pagu vs realisasi per RKA/Sub Kegiatan
- **Role & Login**: Admin, PPTK, Bendahara (role-based access sederhana)

## Cara Instalasi
1. **Siapkan database** dan buat schema:
   ```sql
   -- di MySQL/MariaDB
   CREATE DATABASE rka_spj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE rka_spj;
   SOURCE db/schema.sql; -- jalankan file ini (atau copy isinya)
   ```
2. **Konfigurasi koneksi DB** di `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'rka_spj');
   define('DB_USER', 'root'); // ubah sesuai server Anda
   define('DB_PASS', 'password');
   ```
3. **Set permission folder upload** (Linux/Apache):
   ```bash
   chmod -R 755 uploads
   ```
4. **Buat Admin pertama** via halaman setup:
   - Jalankan aplikasi di browser lalu akses: `/public/setup_admin.php`
   - Isi nama, email, dan password admin awal.

5. **Jalankan aplikasi** di server lokal (Apache/Nginx) atau `php -S`:
   ```bash
   php -S localhost:8080 -t public
   # lalu buka http://localhost:8080
   ```

## Catatan Keamanan & Kepatuhan
- File SPJ dibatasi tipe: PDF/JPG/PNG dan ukuran ≤ 10 MB.
- Semua query menggunakan **prepared statements (PDO)**.
- Pastikan pembatasan akses sesuai peran (Admin/PPTK/Bendahara) telah disesuaikan dengan kebutuhan internal.
- Untuk produksi, letakkan folder `uploads` **di luar** webroot dan gunakan reverse proxy untuk pengiriman berkas.

## Struktur Proyek
```
aplikasi-rka-spj-php/
├── config/
│   └── config.php
├── db/
│   └── schema.sql
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── setup_admin.php
│   ├── css/
│   │   └── style.css
│   └── pages/
│       ├── dashboard.php
│       ├── rka.php
│       ├── subkegiatan.php
│       ├── komponen.php
│       ├── penyerapan.php
│       ├── spj.php
│       └── realisasi.php
├── src/
│   └── lib/
│       ├── Database.php
│       └── Auth.php
└── uploads/
    └── spj/
```

## Penyesuaian
- Tambah kolom atau validasi sesuai regulasi internal (mis. kode rekening, nomor DPA, SPTJB, dll.).
- Tambahkan export Excel/PDF jika diperlukan (contoh dasar mudah ditambahkan).

Semoga membantu mempercepat pekerjaan PPTK 😄
