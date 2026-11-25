# Fast Wash

Aplikasi manajemen operasional laundry modern dan layanan pelanggan digital berbasis web (Proyek PBL). Dibangun menggunakan **PHP 8.3/8.4 (prosedural & native)**, **PostgreSQL 17 (PDO)**, **Tailwind CSS v4 + Flowbite**, integrasi email transaksional via **Brevo (Sendinblue) REST API**, sistem **Database Migration Runner native**, serta orkestrasi container modern menggunakan **Docker Compose & Coolify**.

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur-Fitur Utama](#-fitur-fitur-utama)
- [Struktur Direktori & Isi Proyek](#-struktur-direktori--isi-proyek)
- [Routing Front Controller (`index.php`)](#-routing-front-controller-indexphp)
- [Sistem Migrasi Database Native](#-sistem-migrasi-database-native)
- [Panduan Menjalankan Proyek](#-panduan-menjalankan-proyek)
  - [1. Prasyarat Sistem](#1-prasyarat-sistem)
  - [2. Kloning & Konfigurasi Lingkungan (`.env`)](#2-kloning--konfigurasi-lingkungan-env)
  - [3. Setup & Migrasi Basis Data](#3-setup--migrasi-basis-data)
  - [4. Menjalankan Server Aplikasi](#4-menjalankan-server-aplikasi)
  - [5. Kompilasi Tailwind CSS (Opsional)](#5-kompilasi-tailwind-css-opsional)
- [Deployment & CI/CD](#-deployment--cicd)
- [Alur Kerja Kontribusi Tim](#-alur-kerja-kontribusi-tim)

---

## 💡 Tentang Proyek

**Fast Wash** dirancang untuk memodernisasi tata kelola operasional bisnis laundry mulai dari skala UMKM hingga multi-cabang. Sistem ini menghubungkan kebutuhan operasional kasir, manajemen pemilik usaha, serta kenyamanan pelanggan dalam satu ekosistem terpadu:

1. **Efisiensi Kasir & Karyawan**: Mempercepat input pesanan baru, kalkulasi harga otomatis, pembaruan status pengerjaan bertahap, pencetakan nota kasir thermal, serta notifikasi otomatis ke WhatsApp pelanggan.
2. **Kenyamanan Pelanggan**: Pelanggan dapat memantau progres cucian mereka secara mandiri dan *real-time* melalui **Nota Digital** tanpa perlu login atau menginstal aplikasi tambahan.
3. **Kendali Penuh Pemilik (Owner)**: Pemilik bisnis mendapatkan visibilitas performa usaha melalui dasbor analitik, grafik omzet harian/bulanan, riwayat audit trail log aktivitas, dan pengelolaan kas keuangan.
4. **Keandalan Arsitektur**: Mengusung arsitektur PHP prosedural yang ringan, terstruktur, bebas dependensi framework *heavyweight*, dilengkapi sistem migrasi database otomatis, serta siap di-deploy secara kontainerisasi.

---

## ✨ Fitur-Fitur Utama

### 1. 🔐 Autentikasi, Hak Akses & Keamanan
- **Multi-Role (RBAC)**: Pemisahan akses ketat antara peran **Pemilik (Owner)** dan **Karyawan (Staff)**.
- **Login & Registrasi Aman**: Hash password menggunakan algoritma native `PASSWORD_BCRYPT`.
- **Reset Password via Email (Brevo REST API)**:
  - Alur lupa kata sandi (*Forgot Password*) dengan token unik kedaluwarsa (1 jam).
  - Tautan reset dikirim langsung ke email pengguna via **Brevo REST API (HTTPS port 443 via cURL)**, kebal terhadap pemblokiran port SMTP lokal (port 25/587/465) oleh ISP atau VPS hosting.
- **Proteksi CSRF & Sanitasi XSS**:
  - Validasi token CSRF pada seluruh form POST menggunakan `includes/csrf.php`.
  - Fungsi utilitas `e()` untuk sanitasi output HTML guna mencegah kerentanan XSS.

### 2. 📊 Dasbor Operasional & Analitik
- **4 Kartu Metrik KPI Real-Time**:
  - *Pesanan Hari Ini* (beserta perbandingan selisih dengan hari kemarin).
  - *Sedang Diproses* (jumlah cucian aktif yang sedang dikerjakan).
  - *Siap Diambil* (cucian selesai yang menunggu penyerahan / pelunasan).
  - *Jam Kerja Karyawan* (durasi kerja aktif dan status check-in hari ini).
- **Antrean Pengerjaan Pesanan**: Tabel antrean dinamis dengan badge status warna yang intuitif.
- **Aksi Cepat Kasir**: Tombol pintasan langsung untuk membuat pesanan baru dalam hitungan detik.

### 3. 🧺 Manajemen Pesanan & Transaksi Kasir
- **Input Pesanan Cepat**: Pemilihan pelanggan, penentuan layanan (kiloan/satuan/ekspres), bobot/kuantitas, dan catatan khusus cucian.
- **Alur 6 Tahap Pengerjaan Cucian**:
  $$\text{Antrean} \longrightarrow \text{Dicuci} \longrightarrow \text{Dikeringkan} \longrightarrow \text{Disetrika} \longrightarrow \text{Selesai} \longrightarrow \text{Diambil}$$
- **Aksi Undo Status**: Kemampuan membatalkan atau menurunkan status pengerjaan kembali ke tahap sebelumnya jika terjadi kesalahan input oleh kasir.
- **Detail Pesanan Komprehensif**: Rincian pakaian, timeline riwayat perubahan status, dan kalkulasi pembayaran.
- **Cetak Nota & Integrasi WhatsApp**:
  - Cetak struk kasir thermal 58mm/80mm siap printer POS.
  - Tombol **Kirim WhatsApp** otomatis yang menyusun pesan ucapan resmi kasir lengkap dengan tautan unik Nota Digital pelanggan.

### 4. 📱 Nota Digital & Pelacakan Real-Time Pelanggan (`/nota/{token}`)
- **Akses Publik Aman**: Pelanggan mengakses nota digital melalui URL cantik dengan 32-karakter token unik (contoh: `/nota/4f8a9b2c3d4e5f60718293a4b5c6d7e8`) tanpa perlu kata sandi.
- **Pembaruan Status Real-Time (Live Polling)**:
  - Menggunakan API endpoint `pelanggan/api_status.php` dengan polling asinkron per 5 detik.
  - Ketika kasir memperbarui status cucian, halaman nota pelanggan langsung berpindah status secara instan tanpa perlu memuat ulang (*refresh*) browser.
- **Antarmuka Mobile-First**: Tampilan modern, kartu sapaan pelanggan, progres timeline visual, rincian biaya, status pembayaran (Lunas / Belum Lunas), dan QR code.

### 5. 💳 Manajemen Pembayaran & Kasir
- **Pencatatan Multi-Metode**: Mendukung pembayaran Tunai (*Cash*), Transfer Bank, dan QRIS / E-Wallet.
- **Dua Tampilan Kerja**: Tab **Riwayat Transaksi** (semua pembayaran yang masuk) dan Tab **Tagihan Belum Lunas** (pesanan yang belum dibayar).
- **Fitur Void Pembayaran**: Otorisasi pembatalan transaksi salah bayar dengan pencatatan alasan pembatalan dan otomatis mengembalikan status pesanan menjadi belum lunas.
- **Filter Pencarian Lengkap**: Filter berdasarkan rentang tanggal, status, metode pembayaran, kata kunci nama/kode, serta navigasi paginasi.

### 6. 📈 Laporan Keuangan & Analitik Pendapatan (`/laporan`)
- **Proteksi Akses Khusus Pemilik (*Owner Guard*)**: Akses otomatis dialihkan jika diakses oleh akun non-pemilik.
- **Grafik Interaktif (ApexCharts)**: Visualisasi tren pendapatan omzet harian, mingguan, bulanan, maupun tahunan.
- **Ringkasan Finansial**: Total pendapatan kotor, total transaksi selesai, rata-rata nilai transaksi, dan piutang belum tertagih.
- **Filter Fleksibel**: Pilihan cepat (Harian, Mingguan, Bulanan, Tahunan) dan filter rentang tanggal kustom (*date range picker*).

### 7. ⚙️ Manajemen Layanan & Tarif (`/pengaturan`)
- Pengelolaan katalog paket laundry (Cuci Kering, Cuci + Setrika, Setrika Saja, Bed Cover, dll.).
- Pengaturan tarif per kilogram/satuan, estimasi durasi pengerjaan, dan status keaktifan layanan.

### 8. 🕒 Absensi & Jam Kerja Karyawan (`/absensi`)
- Pencatatan kehadiran digital harian (Check-in dan Check-out karyawan).
- Perhitungan durasi jam kerja karyawan dan rekapitulasi kehadiran untuk transparansi operasional.

### 9. 🛡️ Audit Trail & Log Aktivitas Sistem (`/log-activity`)
- **Pencatatan Otomatis (*Tamper-Proof / Immutable*)**: Seluruh aktivitas krusial sistem (pembuatan pesanan, pembaruan status, pembayaran, login/logout, void) dicatat tanpa opsi edit/hapus.
- **Filter Audit**: Filter berdasarkan peran pengguna (*Pemilik* / *Karyawan*), kategori modul, pencarian kata kunci, serta pencatatan otomatis alamat IP (*IP Address*).
- **Ekspor Data Log**: Fitur ekspor rekaman audit trail untuk kebutuhan pelaporan kepatuhan dan keamanan.

### 10. ⚡ Otomasi Frontend & Anti-Cache (`asset_url()`)
- Seluruh pemanggilan file CSS dan JavaScript menggunakan fungsi pembantu `asset_url()`.
- Menambahkan parameter query versi dinamis berdasarkan waktu modifikasi berkas (`filemtime`, misal: `style.css?v=1732501234`), mencegah *caching issue* pada browser pengguna atau CDN Cloudflare saat ada pembaruan kode.

---

## 📁 Struktur Direktori & Isi Proyek

Proyek ini menggunakan struktur modular dengan pemisahan tanggung jawab yang jelas:

```text
fast-wash-pbl/
├── auth/                           # Modul Autentikasi & Akun
│   ├── login.php                   # Tampilan form login
│   ├── proses_login.php            # Verifikasi kredensial & inisialisasi sesi
│   ├── register.php                # Tampilan form pendaftaran akun
│   ├── proses_register.php         # Handler validasi & simpan akun baru
│   ├── forgot_password.php         # Form permohonan reset kata sandi
│   ├── reset_password.php          # Form ubah password baru melalui token email
│   ├── proses_reset_password.php   # Pembaruan password terenkripsi di database
│   └── logout.php                  # Penghancuran sesi login
├── dashboard/                      # Modul Dasbor Utama
│   └── index.php                   # Halaman KPI operasional & antrean pesanan
├── pesanan/                        # Modul Pengelolaan Pesanan Laundry
│   ├── list.php                    # Tabel daftar seluruh pesanan
│   ├── tambah.php                  # Form input pesanan baru kasir
│   ├── detail.php                  # Halaman detail pesanan & kontrol alur pengerjaan
│   ├── sukses.php                  # Halaman konfirmasi setelah pesanan berhasil dibuat
│   ├── proses_tambah.php           # Handler simpan pesanan & generate token nota
│   ├── proses_edit.php             # Handler perbaikan data pesanan
│   ├── proses_undo.php             # Handler pembatalan status (mundur 1 tahap)
│   └── hapus.php                   # Penghapusan pesanan (soft/hard delete)
├── transaksi/                      # Modul Transaksi & Kasir
│   ├── bayar.php                   # Antarmuka pembayaran langsung pesanan
│   ├── nota.php                    # Struk thermal kasir & tombol kirim WhatsApp
│   ├── riwayat.php                 # Arsip riwayat transaksi pengerjaan
│   └── proses_status.php           # Handler pembaruan tahap status cucian
├── pelanggan/                      # Modul Pelanggan & Nota Digital
│   ├── list.php, tambah.php        # Manajemen master data pelanggan
│   ├── edit.php, hapus.php         # Modifikasi data kontak & alamat pelanggan
│   ├── pesanan.php                 # Antarmuka Nota Digital live tracking pelanggan
│   └── api_status.php              # Endpoint JSON untuk live status polling
├── pembayaran/                     # Modul Pembayaran Kas (Pola MVC Ringan)
│   ├── index.php                   # Entry point modul pembayaran
│   ├── controller.php              # Logika kontrol, validasi, dan otorisasi
│   ├── model.php                   # Kueri database transaksi & void
│   ├── view.php                    # Template UI riwayat transaksi & tagihan
│   ├── proses_tambah.php           # Handler simpan pembayaran baru
│   └── proses_void.php             # Handler pembatalan pembayaran (void)
├── laporan/                        # Modul Laporan Finansial Pemilik (Pola MVC Ringan)
│   ├── index.php                   # Entry point modul laporan
│   ├── controller.php              # Filter tanggal, agregasi omzet & serialisasi grafik
│   ├── model.php                   # Kueri agregat finansial & metrik KPI
│   └── view.php                    # Template UI analitik & grafik ApexCharts
├── pengaturan/                     # Modul Pengaturan Layanan & Tarif
│   ├── index.php                   # Daftar tarif & katalog jenis laundry
│   ├── data_layanan.php            # Helper pengelola data layanan
│   └── proses.php                  # Handler CRUD tarif layanan
├── absensi/                        # Modul Kehadiran Karyawan
│   ├── index.php                   # Antarmuka check-in/out harian
│   ├── rekap.php                   # Laporan kehadiran mingguan/bulanan
│   └── proses.php                  # Handler pencatatan jam absensi
├── log-activity/                   # Modul Audit Trail Keamanan
│   └── index.php                   # Tabel rekaman log aktivitas & filter
├── includes/                       # Library, Helper & Komponen Bersama
│   ├── bootstrap.php               # Inisialisasi awal, load .env, sesi, & base URL
│   ├── config.php                  # Pembaca konfigurasi environment & email
│   ├── koneksi.php                 # Koneksi database PostgreSQL via PDO
│   ├── auth.php                    # Middleware guard proteksi sesi pengguna
│   ├── csrf.php                    # Generator & validator token anti-CSRF
│   ├── helpers.php                 # Helper utilitas (e(), asset_url(), rupiah(), flash)
│   ├── mailer.php                  # Service pengiriman email via Brevo REST API
│   ├── header.php                  # Komponen bilah navigasi atas (top bar)
│   └── sidebar.php                 # Komponen sidebar navigasi modular
├── database/                       # Sistem Basis Data & Migrasi
│   ├── README.md                   # Dokumentasi teknis runner migrasi
│   └── migrations/                 # Berkas-berkas migrasi SQL terurut ber-timestamp
│       ├── 20261101_000001_master_dan_identitas.sql
│       ├── 20261101_000002_pengguna_dan_autentikasi.sql
│       ├── 20261101_000003_pesanan_dan_transaksi.sql
│       ├── 20261101_000004_absensi_dan_log.sql
│       ├── 20261101_000005_indeks_dan_trigger.sql
│       ├── 20261101_000006_views_antarmuka.sql
│       ├── 20261101_000007_data_awal_seed.sql
│       └── 20261125_065535_hapus_duplikasi_layanan.sql
├── migrate.php                     # CLI runner migrasi database native
├── assets/                         # Aset Frontend Statis
│   ├── css/                        # Tailwind source (input.css) & compiled (style.css)
│   ├── js/app.js                   # Logika interaktif DOM, modal & drawer
│   └── img/                        # Aset gambar UI, ikon SVG & ilustrasi
├── sql/                            # Skrip SQL Alternatif / Backup
│   └── fast_wash_all_in_one.sql    # Skrip all-in-one untuk DBeaver/pgAdmin
├── tools/                          # Utilitas lokal Windows (tailwindcss.exe)
├── .env.example                    # Template variabel lingkungan
├── .htaccess                       # Rewrite engine Apache untuk clean URL
├── Dockerfile                      # Konfigurasi container PHP 8.3 Apache
├── docker-compose.staging.yml      # Stack deployment Staging
├── docker-compose.prod.yml         # Stack deployment Production
├── composer.json                   # Definisi script CLI migrasi
└── index.php                       # Front controller & router utama aplikasi
```

---

## 🗺️ Routing Front Controller (`index.php`)

Seluruh lalu lintas URL diatur secara terpusat oleh `index.php`. Berkat file konfigurasi `.htaccess`, semua request diarahkan menjadi URL bersih (*clean URLs*) tanpa ekstensi `.php`:

| URL Route | File Handler | Akses Peran | Deskripsi |
| :--- | :--- | :--- | :--- |
| `/` atau `/index` | `auth/login.php` | Publik | Halaman login sistem |
| `/dashboard` | `dashboard/index.php` | Karyawan & Pemilik | Dasbor ringkasan KPI dan antrean kerja |
| `/auth/login` | `auth/login.php` | Publik | Halaman masuk aplikasi |
| `/auth/register` | `auth/register.php` | Publik | Pendaftaran akun baru |
| `/auth/forgot_password` | `auth/forgot_password.php` | Publik | Permintaan tautan reset password |
| `/auth/reset_password` | `auth/reset_password.php` | Publik | Form reset password via token email |
| `/auth/logout` | `auth/logout.php` | Terautentikasi | Keluar dari sesi aplikasi |
| `/pesanan` | `pesanan/list.php` | Karyawan & Pemilik | Daftar seluruh pesanan laundry |
| `/pesanan/tambah` | `pesanan/tambah.php` | Karyawan & Pemilik | Form tambah pesanan cucian baru |
| `/pesanan/detail` | `pesanan/detail.php` | Karyawan & Pemilik | Detail pesanan, riwayat status & aksi kasir |
| `/transaksi/bayar` | `transaksi/bayar.php` | Karyawan & Pemilik | Form pembayaran transaksi |
| `/transaksi/nota` | `transaksi/nota.php` | Karyawan & Pemilik | Cetak struk kasir & kirim tautan WhatsApp |
| `/pelanggan/list` | `pelanggan/list.php` | Karyawan & Pemilik | Manajemen data pelanggan |
| `/pembayaran` | `pembayaran/index.php` | Karyawan & Pemilik | Riwayat pembayaran & daftar tagihan |
| `/laporan` | `laporan/index.php` | **Khusus Pemilik** | Analitik omzet, grafik pendapatan & keuangan |
| `/pengaturan` | `pengaturan/index.php` | **Khusus Pemilik** | Pengaturan tarif & katalog layanan |
| `/absensi` | `absensi/index.php` | Karyawan & Pemilik | Presensi kehadiran harian staf |
| `/log-activity` | `log-activity/index.php` | Karyawan & Pemilik | Audit trail aktivitas operasional sistem |
| `/nota/{token}` | `pelanggan/pesanan.php` | **Publik (Token)** | **Nota Digital pelanggan & live status tracking** |

> ⚠️ **Aturan Penulisan URL**:
> - Semua atribut `href` dan `action` pada form HTML harus ditulis **tanpa `.php`** (contoh: `<a href="<?= $base ?>dashboard">` atau `<form action="<?= $base ?>pesanan/proses_tambah">`).
> - Mengirimkan form POST langsung ke alamat dengan akhiran `.php` akan terkena HTTP Redirect 301 dari Apache, yang menyebabkan data `$_POST` hilang.

---

## 🗄️ Sistem Migrasi Database Native

Fast Wash dilengkapi **Database Migration Runner** mandiri (native) di berkas [`migrate.php`](file:///E:/fast-wash-pbl/migrate.php) tanpa memerlukan framework eksternal seperti Laravel atau Prisma. Sistem ini memanfaatkan koneksi PDO PostgreSQL yang sudah ada dan mencatat riwayat eksekusi pada tabel `_migrations`.

### Keunggulan Sistem Migrasi:
1. **Transaksi Atomik**: Setiap file migrasi dieksekusi di dalam blok transaksi `BEGIN ... COMMIT`. Jika terjadi galat kueri SQL, seluruh perubahan berkas tersebut otomatis di-*rollback*, menjaga integritas database.
2. **Riwayat Berkelompok (*Batch Tracking*)**: Mengetahui kapan dan berkas mana saja yang dieksekusi bersamaan.
3. **Mencegah Konflik Antar Anggota Tim**: Setiap migrasi diberi awalan waktu berformat `YYYYMMDD_HHMMSS_<nama>.sql` sehingga tidak akan saling menimpa saat penggabungan di Git.

---

### Perintah Dasar CLI Migrasi

Jalankan perintah ini di terminal pada direktori root proyek:

#### 1. Menjalankan Migrasi Baru
```bash
php migrate.php
# atau menggunakan composer:
composer run migrate
```
> Menjalankan seluruh berkas SQL di folder `database/migrations/` yang berstatus `PENDING` secara urut.

#### 2. Melihat Status Migrasi
```bash
php migrate.php status
# atau menggunakan composer:
composer run migrate:status
```
> Menampilkan tabel visual di terminal berisi daftar seluruh migrasi, status (`[APPLIED]` atau `[PENDING]`), nomor batch, dan tanggal eksekusinya.

#### 3. Membuat Berkas Migrasi Baru
```bash
php migrate.php create nama_perubahan
```
*Contoh:*
```bash
php migrate.php create tambah_kolom_poin_pelanggan
```
> Otomatis membuat berkas baru: `database/migrations/YYYYMMDD_HHMMSS_tambah_kolom_poin_pelanggan.sql` yang siap diisi perintah DDL/DML SQL.

#### 4. Baseline (Sinkronisasi Database yang Sudah Ada Isinya)
```bash
php migrate.php baseline
```
> Menandai seluruh berkas migrasi saat ini sebagai `[APPLIED]` ke dalam tabel `_migrations` **tanpa mengeksekusi kueri SQL-nya**. Perintah ini sangat berguna jika Anda sudah memiliki tabel dan data yang lengkap di database lokal agar tidak terjadi error konflik `table already exists`.

---

### Alur Kerja Kolaborasi Migrasi Tim (Team Workflow)

1. **Ketika Anda Ingin Mengubah Skema Database**:
   - Jalankan `php migrate.php create <nama_perubahan>`.
   - Buka berkas SQL yang baru dibuat di `database/migrations/`.
   - Tuliskan kueri `ALTER TABLE`, `CREATE TABLE`, atau `CREATE INDEX` yang dibutuhkan.
   - Uji coba dengan menjalankan `php migrate.php`.
   - Lakukan commit berkas migrasi ke Git: `git add database/migrations/...`.

2. **Ketika Menerima Pembaruan dari Rekan Tim (`git pull`)**:
   - Tarik perubahan terbaru dari GitHub (`git pull`).
   - Cukup jalankan perintah:
     ```bash
     php migrate.php
     ```
   - Skema database lokal Anda akan otomatis terbarui ke versi terkini tanpa perlu menghapus database dari nol atau mengimpor SQL secara manual via DBeaver/pgAdmin.

---

## 🚀 Panduan Menjalankan Proyek

### 1. Prasyarat Sistem
Pastikan komputer atau server Anda memenuhi kebutuhan berikut:
- **PHP**: Versi 8.2, 8.3, atau 8.4 dengan ekstensi aktif:
  - `pdo` & `pdo_pgsql`
  - `curl`
  - `mbstring`
  - `openssl`
- **Database**: PostgreSQL Server versi 15, 16, atau 17.
- **Web Server**: Apache dengan modul `mod_rewrite` aktif (atau PHP CLI built-in server).
- **Composer**: (Opsional) untuk menjalankan pintasan `composer run migrate`.
- **Akun Brevo**: Akun gratis di [Brevo (Sendinblue)](https://app.brevo.com/) untuk mendapatkan API Key pengiriman email.

---

### 2. Kloning & Konfigurasi Lingkungan (`.env`)

1. Buka terminal dan clone repositori:
   ```bash
   git clone https://github.com/firdauzynuzula/fast_wash_project_based_learning.git
   cd fast_wash_project_based_learning
   ```

2. Salin template lingkungan `.env.example` menjadi `.env`:
   ```bash
   # Di Windows PowerShell:
   Copy-Item .env.example .env

   # Di Linux / macOS / Git Bash:
   cp .env.example .env
   ```

3. Buka file `.env` dan sesuaikan parameter berikut:
   ```ini
   # Konfigurasi Koneksi PostgreSQL
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_NAME=fast_wash
   DB_USER=postgres
   DB_PASS=password_database_anda
   DB_SSLMODE=prefer

   # Konfigurasi Aplikasi
   APP_DEBUG=true
   APP_URL=http://localhost:8000
   NOTA_BASE_URL=http://localhost:8000

   # Konfigurasi Email Brevo REST API (Reset Password)
   BREVO_API_KEY=xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   MAIL_FROM_EMAIL=support@fastwash.web.id
   MAIL_FROM_NAME="Fast Wash Official"
   ```

---

### 3. Setup & Migrasi Basis Data

1. Buat database kosong bernama `fast_wash` di PostgreSQL:
   ```sql
   CREATE DATABASE fast_wash;
   ```

2. Eksekusi migrasi skema & seed data awal menggunakan salah satu opsi berikut:

   - **Opsi A: Menggunakan Migration Runner (Sangat Direkomendasikan)**:
     ```bash
     php migrate.php
     ```
     Perintah ini akan membuat tabel `_migrations` dan mengeksekusi seluruh file migrasi dari baseline tabel, indeks, trigger, view dasbor, hingga akun demo awal.

   - **Opsi B: Menggunakan DBeaver / pgAdmin (All-in-One)**:
     Buka file [`sql/fast_wash_all_in_one.sql`](file:///E:/fast-wash-pbl/sql/fast_wash_all_in_one.sql), lalu jalankan seluruh skrip kueri menggunakan `Alt + X`. Setelah itu, jalankan `php migrate.php baseline` agar runner menandai migrasi telah selesai.

---

### 4. Menjalankan Server Aplikasi

Pilih salah satu metode berikut yang paling sesuai dengan alur kerja Anda:

#### Opsi A: PHP Built-in Server (Paling Cepat & Praktis)
Jalankan server PHP lokal dengan menyertakan `index.php` sebagai router penanganan URL:
```bash
php -S localhost:8000 index.php
```
Buka peramban (*browser*) dan akses: **`http://localhost:8000`**.

#### Opsi B: Web Server Apache (Laragon / XAMPP)
- **Laragon**:
  - Pindahkan folder proyek ke `C:\laragon\www\fast-wash-pbl`.
  - Buka Laragon dan klik **Start All**.
  - Buka browser pada alamat: `http://fast-wash-pbl.test/` atau `http://localhost/fast-wash-pbl/`.
  - (*Catatan: Modul `mod_rewrite` dan `AllowOverride All` sudah aktif secara bawaan di Laragon*).
- **XAMPP**:
  - Letakkan di `C:\xampp\htdocs\fast-wash-pbl`.
  - Pastikan pada konfigurasi `httpd.conf` Apache direktif `AllowOverride` bernilai `All` dan modul `rewrite_module` diaktifkan (`LoadModule rewrite_module modules/mod_rewrite.so`).

#### Opsi C: Menjalankan via Docker Compose
Jika Anda memiliki Docker Desktop yang terpasang:
```bash
# Menjalankan container aplikasi beserta database PostgreSQL 17
docker compose -f docker-compose.staging.yml up -d --build
```
Aplikasi akan aktif dan terhubung langsung ke kontainer basis data terisolasi.

---

### 5. Kompilasi Tailwind CSS (Opsional)

Stylesheet yang telah dikompilasi sudah disertakan langsung di berkas [`assets/css/style.css`](file:///E:/fast-wash-pbl/assets/css/style.css) sehingga aplikasi **siap pakai tanpa perlu proses build**.

Jika Anda memodifikasi tampilan antarmuka, file template PHP, atau file sumber `assets/css/input.css`, lakukan kompilasi menggunakan Tailwind CLI lokal:

```bash
# Kompilasi minified sekali eksekusi
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --minify

# Mode pemantauan otomatis (watch mode saat development)
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --watch
```

---

## 🌐 Deployment & CI/CD

Proyek ini telah dikonfigurasi untuk lingkungan staging dan production berbasis **Docker Compose** dan manajemen PaaS modern seperti **Coolify**:

| Lingkungan | Branch Git | Target Domain Utama | Target Domain Nota Digital | Alur Rilis |
| :--- | :--- | :--- | :--- | :--- |
| **Staging** | `staging` | `https://staging.gapunyaide.web.id` | `https://staging-nota.gapunyaide.web.id` | **Auto-Deploy** via GitHub Webhook |
| **Production** | `production` | `https://fastwash.web.id` | `https://nota.fastwash.web.id` | **Manual Approval** via Dashboard Coolify |

- **Security Hardening Container**:
  - `expose_php = Off` dan `ServerTokens Prod` untuk menyembunyikan versi server dari celah pemindaian publik.
  - Reverse Proxy (Traefik) dengan sertifikat SSL gratis otomatis dari Let's Encrypt.
- **Panduan Deployment Coolify**: Rincian langkah demi langkah konfigurasi Coolify dan integrasi Cloudflare dapat dibaca pada dokumen [**`prompt/docs/tahapan-tahapan-ci-cd.md`**](file:///E:/fast-wash-pbl/prompt/docs/tahapan-tahapan-ci-cd.md).

---

## 🤝 Alur Kerja Kontribusi Tim

Kolaborasi pengembangan Fast Wash menggunakan alur kerja standar **Fork & Pull Request Workflow**:

```text
+-----------------------------------------------------------------------+
|  Repositori Utama (upstream)                                          |
|  https://github.com/firdauzynuzula/fast_wash_project_based_learning   |
+-----------------------------------------------------------------------+
          |                                             ^
          | 1. Fork (di GitHub)                         | 6. Pull Request (PR)
          |    & Sync (git fetch upstream)              |
          v                                             |
+-----------------------------------+                   |
|  Repositori Fork Anda (origin)    |-------------------+
|  github.com/<username>/...        |
+-----------------------------------+
          |                       ^
          | 2. git clone          | 5. git push origin feat/...
          v                       |
+-----------------------------------+
|  Komputer Lokal Anda (working)    |
|  Branch: feat/nama-fitur          |
+-----------------------------------+
```

### Langkah-Langkah Berkontribusi:

1. **Fork Repositori**: Klik tombol **Fork** pada halaman GitHub repositori utama.
2. **Clone ke Lokal**:
   ```bash
   git clone https://github.com/<username-anda>/fast_wash_project_based_learning.git
   cd fast_wash_project_based_learning
   ```
3. **Tambahkan Remote Upstream**:
   ```bash
   git remote add upstream https://github.com/firdauzynuzula/fast_wash_project_based_learning.git
   git remote -v
   ```
4. **Buat Branch Fitur Baru**:
   ```bash
   git checkout master
   git checkout -b feat/nama-fitur
   ```
5. **Cek Sintaks PHP Sebelum Commit**:
   ```bash
   php -l nama_file_yang_diubah.php
   ```
6. **Commit & Push ke Remote Fork Anda**:
   ```bash
   git add .
   git commit -m "feat: deskripsi jelas perubahan fitur"
   git push -u origin feat/nama-fitur
   ```
7. **Buka Pull Request (PR)**: Masuk ke halaman GitHub repositori *fork* Anda, klik **Compare & pull request**, lalu ajukan ke branch `master` repositori upstream.
8. **Sinkronisasi Berkala (*Sync Fork*)**:
   ```bash
   git fetch upstream
   git checkout master
   git merge upstream/master
   git push origin master
   ```

---

## 📄 Lisensi & Hak Cipta

Proyek ini dikembangkan untuk keperluan akademik **Project Based Learning (PBL)** dan dilisensikan di bawah lisensi [MIT](file:///E:/fast-wash-pbl/composer.json).
