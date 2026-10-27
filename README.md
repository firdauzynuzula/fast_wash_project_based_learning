# Fast Wash

Aplikasi manajemen laundry (proyek PBL). PHP prosedural + PostgreSQL (PDO), struktur mengikuti referensi jobsheet-14.

## Struktur

```
index.php            Router (front controller): daftar URL -> file tujuan ($routes)
beranda.php          Beranda / dashboard
pelanggan/           CRUD pelanggan (list, tambah, proses_tambah, edit, proses_edit, hapus)
layanan/             CRUD layanan  (pola sama)
transaksi/           tambah, proses_tambah, status, proses_status, riwayat
auth/                login, register, logout (+ proses_*)
includes/            bootstrap, config, koneksi, auth (guard), csrf, helpers, header, footer
assets/              css (Tailwind hasil compile), js, img
sql/                 skema PostgreSQL lengkap (jalankan 00 -> 07 atau fast_wash_all_in_one.sql)
docs/                catatan (security-checklist)
tools/               tailwindcss.exe (CLI lokal, di-gitignore)
```

## Setup

1. **Database**: 
   - Buat database `fast_wash` di DBeaver.
   - Jalankan skrip DDL & DML:
     - **Opsi Cepat:** Jalankan satu file lengkap [`sql/fast_wash_all_in_one.sql`] di DBeaver, execute semua query menggunakan Alt + X.
     - **Opsi Bertahap:** Jalankan berurutan menggunamakan Ctrl + Enter:
       1. [`sql/01_master_dan_identitas.sql`] (Pelanggan, Pemilik, Karyawan, Layanan)
       2. [`sql/02_pengguna_dan_autentikasi.sql`] (Pengguna, Sesi Login, Reset Password)
       3. [`sql/03_pesanan_dan_transaksi.sql`] (Pesanan, Detail Pesanan, Riwayat Status, Riwayat Transaksi)
       4. [`sql/04_absensi_dan_log.sql`] (Absensi, Log Aktifitas)
       5. [`sql/05_indeks_dan_trigger.sql`] (Indeks & Trigger Bisnis)
       6. [`sql/06_views_antarmuka.sql`] (View untuk Layar Dasbor, Pesanan, Nota, Laporan)
       7. [`sql/07_data_awal_seed.sql`] (Data Awal Layanan, Akun Demo, Sample Transaksi)
2. **Kredensial**: salin/edit file `.env` di root (otomatis dimuat `includes/bootstrap.php`). Jangan commit `.env` — sudah di-gitignore.
3. **Web server**: Apache (XAMPP/Laragon) dengan `mod_rewrite` aktif dan `AllowOverride All` untuk folder project — wajib agar `.htaccess` (URL tanpa `.php`) bekerja. Laragon: sudah default. XAMPP: edit `httpd.conf`.
4. **Tailwind** (hanya jika mengubah CSS): compile ulang dengan

   ```
   tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --minify
   ```

   Tambah `--watch` saat development. `assets/css/style.css` sudah dikomit — build tidak wajib untuk sekadar menjalankan aplikasi.

## Catatan

- URL tanpa ekstensi: `/pelanggan/list`, bukan `/pelanggan/list.php` (diatur `.htaccess`). **Semua `href` dan `action` form ditulis tanpa `.php`** — POST ke URL `.php` akan kena redirect 301 dan kehilangan body.
- `composer.json` hanya metadata (tidak menghilangkan `.php`; itu kerja `.htaccess`). Tidak perlu `composer install`.
- Routing: `.htaccess` mengarahkan semua URL yang bukan file asli ke `index.php`, yang mencocokkan URL dengan array `$routes`. URL yang tidak terdaftar -> 404. **Halaman baru wajib didaftarkan di `$routes`.**
- `php -S` (server bawaan PHP) mengabaikan `.htaccess`; jalankan dengan router: `php -S localhost:8000 index.php`.
- Artefak deploy (Dockerfile, render.yaml) sengaja tidak disertakan — salin dari repo referensi saat mau deploy (Render + Supabase).
