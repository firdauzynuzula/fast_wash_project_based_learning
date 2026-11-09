# Fast Wash

Aplikasi manajemen operasional laundry dan layanan pelanggan berbasis web (Proyek PBL). Dibangun menggunakan **PHP 8.3 (prosedural)**, **PostgreSQL (PDO)**, **Tailwind CSS**, serta integrasi email via **Brevo (Sendinblue) REST API**.

---

## 🚀 Fitur Utama

- **Autentikasi & Reset Password via Email**:
  - Login dan register pengguna (Karyawan & Pemilik).
  - Alur lupa kata sandi (*Forgot Password*) dengan token kedaluwarsa (1 jam) yang dikirim langsung ke email pengguna menggunakan **Brevo REST API via cURL HTTPS** (port 443, aman dari pemblokiran port SMTP lokal).
- **Dasbor Operasional & Analitik**:
  - Kartu metrik KPI harian real-time (*Pesanan Hari Ini*, *Sedang Diproses*, *Siap Diambil*, *Jam Kerja/Check-in*).
  - Antrean pengerjaan pesanan terbaru beserta status badge dinamis.
  - Aksi cepat pembuatan pesanan baru.
- **Komponen Navigasi & Sidebar Responsif**:
  - Sidebar terpadu dengan navigasi modular (*Dasbor*, *Pesanan*, *Pelanggan*, *Pembayaran*, *Absensi*, *Log Aktivitas*).
  - *Mobile drawer* responsif dengan backdrop dan kartu profil pengguna yang sedang login.
- **Audit Trail / Log Aktivitas Sistem**:
  - Pencatatan aktivitas sistem lengkap (*immutable ledger* tanpa opsi edit/hapus).
  - Filter pencarian nama/aktivitas, filter peran (*Pemilik* / *Karyawan*), filter kategori, dan pencatatan IP Address.
  - Desain tabel modern dengan paginasi dan tombol ekspor data log.
- **Nota Digital & Tracking Pesanan Pelanggan (`/nota`)**:
  - Antarmuka ramah perangkat seluler untuk memantau progres cucian aktif dan riwayat status pengerjaan secara langsung oleh pelanggan.
- **Manajemen Pesanan & Pelanggan**:
  - CRUD Data Pelanggan dan Pesanan Laundry.
- **Infrastruktur Modern (Docker & CI/CD)**:
  - Dockerized menggunakan image resmi `php:8.3-apache-bookworm` dengan *security hardening*.
  - Orkestrasi multi-environment menggunakan `docker-compose.staging.yml` dan `docker-compose.prod.yml`.
  - Otomasi deployment melalui `Jenkinsfile` (*linting syntax*, *auto-deploy staging*, dan *manual approval gate* untuk *production*).

---

## 📁 Struktur Direktori

```text
fast-wash-pbl/
├── auth/                       # Alur autentikasi & akun
│   ├── login.php               # Halaman login
│   ├── proses_login.php        # Handler verifikasi login
│   ├── register.php            # Halaman pendaftaran
│   ├── proses_register.php     # Handler pendaftaran
│   ├── forgot_password.php     # Form permintaan reset password
│   ├── reset_password.php      # Form pembuatan password baru (validasi token)
│   ├── proses_reset_password.php # Handler pembaruan password di database
│   └── logout.php              # Handler keluar sistem
├── dashboard/                  # Modul dasbor utama
│   └── index.php               # Halaman dasbor metrik & antrean kerja
├── pelanggan/                  # Modul pelanggan & tracking
│   ├── list.php, tambah.php    # CRUD Pelanggan (form & tabel)
│   ├── edit.php, hapus.php     # Modifikasi & hapus data pelanggan
│   ├── proses_*.php            # Handler proses CRUD pelanggan
│   └── pesanan.php             # Tampilan nota digital & tracking pelanggan (/nota)
├── pesanan/                    # Modul pesanan laundry (CRUD pesanan)
│   ├── list.php, tambah.php, edit.php, hapus.php
│   └── proses_*.php
├── transaksi/                  # Modul transaksi & alur status cucian
│   ├── tambah.php, status.php, riwayat.php
│   └── proses_*.php
├── log-activity/               # Modul audit trail & log aktivitas
│   └── index.php               # Halaman tabel log aktivitas & filter
├── includes/                   # Library, helper, dan komponen bersama
│   ├── bootstrap.php           # Inisialisasi awal (load .env & session)
│   ├── config.php              # Loader konfigurasi aplikasi & email
│   ├── koneksi.php             # Koneksi database PostgreSQL via PDO
│   ├── auth.php                # Middleware / guard proteksi halaman
│   ├── csrf.php                # Proteksi CSRF token
│   ├── helpers.php             # Fungsi utilitas (e(), flash message, format tanggal/rupiah)
│   ├── mailer.php              # Service pengiriman email via Brevo REST API
│   ├── header.php              # Komponen topbar & navigasi atas
│   └── sidebar.php             # Komponen sidebar navigasi utama
├── assets/                     # File statis frontend
│   ├── css/                    # Tailwind source (input.css) & compiled (style.css)
│   ├── js/app.js               # Script interaktif (sidebar, dropdown, modal)
│   └── img/                    # Aset visual (auth, dashboard, mobile, svg)
├── sql/                        # Skrip database PostgreSQL
│   ├── 00_database.sql s/d 07_data_awal_seed.sql
│   └── fast_wash_all_in_one.sql # Skema & seed data all-in-one
├── tools/                      # CLI pendukung (tailwindcss.exe lokal)
├── .env.example                # Template konfigurasi variabel lingkungan
├── .htaccess                   # Rewrite engine Apache untuk URL bersih tanpa .php
├── Dockerfile                  # Konfigurasi container PHP 8.3 Apache + PDO pgsql
├── docker-compose.staging.yml  # Orkestrasi Docker untuk environment Staging
├── docker-compose.prod.yml     # Orkestrasi Docker untuk environment Production
├── Jenkinsfile                 # Pipeline CI/CD Jenkins (Lint -> Staging -> Production)
└── index.php                   # Front controller & router aplikasi
```

---

## 🗺️ Routing Aplikasi (`index.php`)

Routing dikelola secara terpusat oleh `index.php` sebagai *front controller*. Request yang masuk melalui `.htaccess` diarahkan ke URL tanpa ekstensi `.php`:

| URL Route | File Tujuan | Deskripsi |
| :--- | :--- | :--- |
| `/` atau `/index` | `auth/login.php` | Halaman login default |
| `/dashboard` | `dashboard/index.php` | Dasbor metrik karyawan & pemilik |
| `/auth/login` | `auth/login.php` | Halaman masuk |
| `/auth/register` | `auth/register.php` | Pendaftaran akun baru |
| `/auth/forgot_password` | `auth/forgot_password.php` | Permintaan kirim tautan reset kata sandi |
| `/auth/reset_password` | `auth/reset_password.php` | Halaman ubah kata sandi melalui token email |
| `/auth/logout` | `auth/logout.php` | Sesi keluar akun |
| `/pelanggan/list` | `pelanggan/list.php` | Daftar seluruh pelanggan |
| `/transaksi/status` | `transaksi/status.php` | Pembaruan status pesanan |
| `/transaksi/riwayat` | `transaksi/riwayat.php` | Riwayat transaksi |
| `/log-activity` | `log-activity/index.php` | Audit trail aktivitas sistem |
| `/nota` | `pelanggan/pesanan.php` | Nota digital & pelacakan status cucian pelanggan |

> **Penting**: Semua atribut `href` dan `action` pada form HTML harus ditulis **tanpa `.php`** (contoh: `<a href="<?= $base ?>dashboard">`). Form POST ke file `.php` langsung akan teralihkan melalui redirect 301 dan kehilangan data payload POST.

---

## ⚙️ Panduan Setup & Instalasi

### 1. Prasyarat Sistem
- PHP 8.2 atau 8.3 dengan ekstensi: `pdo`, `pdo_pgsql`, `curl`, `mbstring`, `openssl`.
- PostgreSQL Database Server (versi 15 atau lebih baru).
- Web Server Apache dengan modul `mod_rewrite` aktif.
- Akun [Brevo](https://www.brevo.com/) (gratis) untuk pengiriman email reset password.

---

### 2. Setup Database PostgreSQL
1. Buat database baru bernama `fast_wash` (misalnya via DBeaver atau pgAdmin).
2. Eksekusi skrip SQL:
   - **Opsi Cepat (Direkomendasikan)**: Buka dan jalankan seluruh isi file [`sql/fast_wash_all_in_one.sql`](file:///E:/fast-wash-pbl/sql/fast_wash_all_in_one.sql) (di DBeaver: `Alt + X`).
   - **Opsi Bertahap**: Jalankan berurutan:
     1. `sql/01_master_dan_identitas.sql`
     2. `sql/02_pengguna_dan_autentikasi.sql`
     3. `sql/03_pesanan_dan_transaksi.sql`
     4. `sql/04_absensi_dan_log.sql`
     5. `sql/05_indeks_dan_trigger.sql`
     6. `sql/06_views_antarmuka.sql`
     7. `sql/07_data_awal_seed.sql`

---

### 3. Konfigurasi Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env` di direktori root aplikasi:

```bash
cp .env.example .env
```

Sesuaikan nilai variabel lingkungan pada `.env`:

```ini
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=fast_wash
DB_USER=postgres
DB_PASS=rahasia_anda
DB_SSLMODE=prefer
APP_DEBUG=true
NOTA_BASE_URL=http://localhost:8000

# Konfigurasi Pengiriman Email via Brevo API
BREVO_API_KEY=xkeysib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_EMAIL=support@fastwash.web.id
MAIL_FROM_NAME="Fast Wash Official"
APP_URL=http://localhost:8000
```

---

### 4. Menjalankan Aplikasi

#### Opsi A: Apache (Laragon / XAMPP)
- **Laragon**: Letakkan proyek di direktori `www/`. Secara bawaan modul `mod_rewrite` dan `AllowOverride All` sudah aktif. Akses melalui `http://fast-wash-pbl.test/` atau `http://localhost/fast-wash-pbl/`.
- **XAMPP**: Pastikan pada `httpd.conf` Apache direktif `AllowOverride` telah diatur ke `All` dan modul `rewrite_module` diaktifkan.

#### Opsi B: PHP Built-in Server (Development Cepat)
Jalankan server PHP bawaan dengan menyertakan `index.php` sebagai router:
```bash
php -S localhost:8000 index.php
```
Buka browser pada alamat `http://localhost:8000`.

#### Opsi C: Menggunakan Docker Compose
Aplikasi telah dilengkapi konfigurasi Docker lengkap beserta database PostgreSQL:

- **Environment Staging**:
  ```bash
  docker compose -f docker-compose.staging.yml up -d --build
  ```
- **Environment Production**:
  ```bash
  docker compose -f docker-compose.prod.yml up -d --build
  ```

---

### 5. Kompilasi Tailwind CSS (Opsional)
CSS hasil kompilasi Tailwind sudah disertakan pada `assets/css/style.css`. Jika Anda melakukan modifikasi styling pada `assets/css/input.css` atau markup template HTML/PHP, lakukan *build* ulang menggunakan CLI lokal:

```bash
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --minify
```

Gunakan flag `--watch` untuk memantau perubahan file secara otomatis selama proses pengembangan:
```bash
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --watch
```

---

## 🔄 Integrasi CI/CD & Deployment

Pipeline CI/CD terotomatisasi didefinisikan pada [`Jenkinsfile`](file:///E:/fast-wash-pbl/Jenkinsfile) dengan tahapan:
1. **Checkout**: Mengambil kode sumber dari repositori GitHub.
2. **Syntax Check (Lint)**: Memvalidasi integritas seluruh file sintaks PHP (`php -l`).
3. **Deploy to Staging**: Dijalankan otomatis ketika ada commit/merge ke branch `staging`.
   - Domain Layanan: `https://staging.fastwash.web.id`
   - Domain Nota: `https://staging-nota.fastwash.web.id`
4. **Deploy to Production**: Dijalankan pada branch `production` dengan **persetujuan manual (*manual approval gate*)** di antarmuka Jenkins sebelum rilis ke server produksi.
   - Domain Layanan: `https://fastwash.web.id`
   - Domain Nota: `https://nota.fastwash.web.id`

---

## 📝 Kontribusi & Catatan Pengembangan

- Seluruh endpoint halaman baru wajib didaftarkan pada array `$routes` di [`index.php`](file:///E:/fast-wash-pbl/index.php).
- Pastikan selalu menggunakan fungsi `e()` dari [`includes/helpers.php`](file:///E:/fast-wash-pbl/includes/helpers.php) saat mencetak variabel ke HTML untuk mencegah kerentanan XSS.
- Lindungi form penting (seperti pendaftaran dan proses status) menggunakan token CSRF dari [`includes/csrf.php`](file:///E:/fast-wash-pbl/includes/csrf.php).
