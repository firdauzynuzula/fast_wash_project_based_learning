# Fast Wash

Aplikasi manajemen operasional laundry dan layanan pelanggan berbasis web (Proyek PBL). Dibangun menggunakan **PHP 8.3/8.4 (prosedural)**, **PostgreSQL 17 (PDO)**, **Tailwind CSS v4 + Flowbite**, serta integrasi email transaksional via **Brevo (Sendinblue) REST API** dan orkestrasi deployment modern menggunakan **Docker Compose & Coolify**.

---

## 🚀 Fitur Utama

- **Autentikasi & Reset Password via Email**:
  - Login dan registrasi pengguna dengan pemisahan peran (*Karyawan* & *Pemilik*).
  - Alur lupa kata sandi (*Forgot Password*) dengan token kedaluwarsa (1 jam) yang dikirim langsung ke email pengguna menggunakan **Brevo REST API via cURL HTTPS** (port 443, aman dari pemblokiran port SMTP pada VPS/hosting).
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
  - CRUD Data Pelanggan dan Pesanan Laundry dengan validasi server-side dan proteksi CSRF.
- **Otomasi Cache Busting (`asset_url()`)**:
  - Seluruh stylesheet CSS dan skrip JavaScript dimuat menggunakan fungsi helper `asset_url()` yang otomatis menambahkan query parameter versi berbasis `filemtime` (contoh: `style.css?v=1731301234`), mencegah masalah tampilan akibat browser/CDN caching usang di Cloudflare.
- **Infrastruktur Modern (Docker, PostgreSQL 17, & Coolify CI/CD)**:
  - Containerization menggunakan base image resmi `php:8.3-apache-bookworm` yang diperkuat dengan *security hardening* (`expose_php = Off`, `ServerTokens Prod`).
  - Database PostgreSQL versi terbaru (**v17-alpine**) dengan inisialisasi otomatis via direktori `docker-entrypoint-initdb.d/`.
  - Orkestrasi deployment multi-environment (**Staging** & **Production**) terkelola melalui **Coolify PaaS** dan reverse proxy Traefik (Auto SSL Let's Encrypt).

---

## 📁 Struktur Direktori

```text
fast-wash-pbl/
├── auth/                       # Alur autentikasi & akun
│   ├── login.php               # Halaman login
│   ├── proses_login.php        # Handler verifikasi login
│   ├── register.php            # Halaman pendaftaran akun
│   ├── proses_register.php     # Handler pendaftaran akun
│   ├── forgot_password.php     # Form permintaan tautan reset password
│   ├── reset_password.php      # Form pembuatan password baru (validasi token)
│   ├── proses_reset_password.php # Handler pembaruan password di database
│   └── logout.php              # Handler keluar sesi
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
│   ├── bootstrap.php           # Inisialisasi awal (load .env, session, $base)
│   ├── config.php              # Loader konfigurasi aplikasi, database & email
│   ├── koneksi.php             # Koneksi database PostgreSQL via PDO
│   ├── auth.php                # Middleware / guard proteksi halaman
│   ├── csrf.php                # Proteksi CSRF token
│   ├── helpers.php             # Fungsi utilitas (e(), asset_url(), flash message, token cache)
│   ├── mailer.php              # Service pengiriman email via Brevo REST API
│   ├── header.php              # Komponen topbar & navigasi atas (cache busted)
│   └── sidebar.php             # Komponen sidebar navigasi utama
├── assets/                     # File statis frontend
│   ├── css/                    # Tailwind source (input.css) & compiled (style.css)
│   ├── js/app.js               # Script interaktif (drawer, eye toggle, toast demo)
│   └── img/                    # Aset visual (auth, dashboard, mobile, svg)
├── docker-entrypoint-initdb.d/ # Skrip SQL modular inisialisasi awal container PostgreSQL 17
│   ├── 01_master_dan_identitas.sql
│   ├── 02_pengguna_dan_autentikasi.sql
│   ├── 03_pesanan_dan_transaksi.sql
│   ├── 04_absensi_dan_log.sql
│   ├── 05_indeks_dan_trigger.sql
│   ├── 06_views_antarmuka.sql
│   └── 07_data_awal_seed.sql
├── sql/                        # Arsip referensi skrip database PostgreSQL
│   └── fast_wash_all_in_one.sql # Skema & seed data all-in-one untuk DBeaver/pgAdmin
├── tools/                      # CLI pendukung (tailwindcss.exe lokal Windows)
├── .env.example                # Template konfigurasi variabel lingkungan
├── .htaccess                   # Rewrite engine Apache untuk URL bersih tanpa .php
├── Dockerfile                  # Image container PHP 8.3 Apache + PDO pgsql
├── docker-compose.staging.yml  # Definisi stack Staging (App + PostgreSQL 17)
├── docker-compose.prod.yml     # Definisi stack Production (App + PostgreSQL 17)
├── Jenkinsfile                 # (Arsip/Deprecated) Digantikan oleh Coolify GitHub Webhook
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

> **Penting**: Semua atribut `href` dan `action` pada form HTML harus ditulis **tanpa `.php`** (contoh: `<a href="<?= $base ?>dashboard">`). Form POST ke file `.php` langsung akan teralihkan melalui redirect 301 dan kehilangan data payload POST. Untuk pemanggilan aset CSS/JS, gunakan `<?= asset_url('assets/...') ?>`.

---

## ⚙️ Panduan Setup & Instalasi Lokal

### 1. Prasyarat Sistem
- **PHP**: Versi 8.2, 8.3, atau 8.4 dengan ekstensi aktif: `pdo`, `pdo_pgsql`, `curl`, `mbstring`, `openssl`.
- **Database**: PostgreSQL Server (versi 15, 16, atau 17).
- **Web Server**: Apache dengan modul `mod_rewrite` aktif (atau PHP CLI built-in server).
- **Akun Brevo**: Akun gratis [Brevo (Sendinblue)](https://www.brevo.com/) untuk pengiriman email transaksional reset password.

---

### 2. Setup Database PostgreSQL

1. Buat database baru bernama `fast_wash` di PostgreSQL lokal Anda.
2. Eksekusi skrip SQL:
   - **Opsi Cepat**: Buka dan jalankan seluruh isi file [`sql/fast_wash_all_in_one.sql`](file:///E:/fast-wash-pbl/sql/fast_wash_all_in_one.sql) (di DBeaver: `Alt + X`).
   - **Opsi Modular**: Eksekusi file SQL di direktori [`docker-entrypoint-initdb.d/`](file:///E:/fast-wash-pbl/docker-entrypoint-initdb.d) secara berurutan dari `01` sampai `07`.

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

### 4. Menjalankan Aplikasi di Komputer Lokal

#### Opsi A: PHP Built-in Server (Paling Cepat & Praktis)
Jalankan server PHP bawaan dengan menyertakan `index.php` sebagai router:
```bash
php -S localhost:8000 index.php
```
Buka browser pada alamat `http://localhost:8000`.

#### Opsi B: Apache (Laragon / XAMPP)
- **Laragon**: Letakkan folder proyek di dalam direktori `laragon/www/`. Modul `mod_rewrite` dan `AllowOverride All` sudah aktif secara bawaan. Akses melalui `http://fast-wash-pbl.test/` atau `http://localhost/fast-wash-pbl/`.
- **XAMPP**: Letakkan di `xampp/htdocs/fast-wash-pbl`. Pastikan pada `httpd.conf` Apache direktif `AllowOverride` bernilai `All` dan modul `rewrite_module` aktif.

#### Opsi C: Menjalankan via Docker Compose Lokal
Pastikan Docker Desktop aktif di komputer Anda, lalu jalankan:
```bash
# Jalankan environment Staging lokal
docker compose -f docker-compose.staging.yml up -d --build
```
Akses aplikasi melalui port yang telah dikonfigurasi.

---

### 5. Kompilasi Tailwind CSS (Pengembangan Tampilan)

CSS hasil kompilasi Tailwind sudah disertakan pada `assets/css/style.css` (siap pakai tanpa build). Jika Anda memodifikasi utility class pada file PHP atau mengubah `assets/css/input.css`, lakukan kompilasi ulang menggunakan CLI lokal:

```bash
# Kompilasi minified sekali jalan
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --minify

# Mode watch (otomatis kompilasi setiap kali ada file disimpan)
tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --watch
```

---

## 🌐 Deployment & CI/CD dengan Coolify

Proyek ini telah dikonfigurasi penuh untuk di-deploy menggunakan **Coolify (Self-Hosted PaaS)** di server VPS. Tidak memerlukan instalasi Jenkins atau konfigurasi Nginx manual di host VPS.

### Lingkungan & Pemetaan Domain

| Environment | Branch Git | Domain Layanan Utama | Domain Nota Digital | Mekanisme Rilis |
| :--- | :--- | :--- | :--- | :--- |
| **Staging** | `staging` | `https://staging.gapunyaide.web.id` | `https://staging-nota.gapunyaide.web.id` | **Auto-Deploy** via GitHub Webhook |
| **Production** | `production` | `https://fastwash.web.id` | `https://nota.fastwash.web.id` | **Manual Approval** (Klik Deploy di Coolify) |

### Ringkasan Langkah Setup di Coolify:
1. Pastikan VPS telah terinstal Coolify (`curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash`).
2. Di Coolify, buat Resource baru dengan tipe **Docker Compose**.
3. Arahkan file compose sesuai environment:
   - **Staging**: Menggunakan `/docker-compose.staging.yml`
   - **Production**: Menggunakan `/docker-compose.prod.yml`
4. Masukkan nama domain pada kolom Domains di service `app`, dan atur environment variables (`DB_PASS`, `BREVO_API_KEY`, dll.).
5. Traefik bawaan Coolify akan otomatis menangani perutean domain dan menerbitkan sertifikat SSL Let's Encrypt secara otomatis.

> 📖 **Panduan Lengkap Coolify & Cloudflare**: Silakan baca dokumen panduan terperinci pada [**`prompt/docs/tahapan-tahapan-ci-cd.md`**](file:///E:/fast-wash-pbl/prompt/docs/tahapan-tahapan-ci-cd.md).

---

## 🤝 Panduan Kontribusi (Forking Workflow)

Kontribusi pada proyek **Fast Wash** menggunakan alur kerja **Fork & Pull Request**. Setiap anggota tim atau kontributor tidak melakukan *push* langsung ke repositori utama (`upstream`), melainkan bekerja melalui repositori hasil *fork* (`origin`) masing-masing.

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

### 1. Fork & Clone Repositori

1. Buka halaman repositori utama di GitHub:  
   [`https://github.com/firdauzynuzula/fast_wash_project_based_learning`](https://github.com/firdauzynuzula/fast_wash_project_based_learning)
2. Klik tombol **Fork** di pojok kanan atas untuk menyalin repositori ke akun GitHub Anda.
3. *Clone* repositori hasil *fork* dari akun GitHub Anda ke komputer lokal:
   ```bash
   git clone https://github.com/<username-anda>/fast_wash_project_based_learning.git
   cd fast_wash_project_based_learning
   ```

---

### 2. Setup Awal Remote Upstream

Tambahkan URL repositori utama sebagai remote bernama `upstream` agar Anda dapat menarik pembaruan kapan saja:

```bash
git remote add upstream https://github.com/firdauzynuzula/fast_wash_project_based_learning.git
```

Pastikan daftar remote sudah memiliki `origin` dan `upstream`:
```bash
git remote -v
```

---

### 3. Alur Pengerjaan Fitur & Membuat Pull Request (PR)

1. **Selalu buat branch baru** untuk setiap fitur atau perbaikan bug (hindari menulis kode langsung di branch `master`):
   ```bash
   git checkout master
   git checkout -b feat/nama-fitur
   ```
2. **Lakukan perubahan kode** dan pastikan tidak ada error sintaks PHP:
   ```bash
   php -l nama_file.php
   ```
3. **Jika mengubah tampilan/class Tailwind CSS**, kompilasi ulang stylesheet:
   ```bash
   tools\tailwindcss.exe -i assets/css/input.css -o assets/css/style.css --minify
   ```
4. **Commit dan Push** ke repositori *fork* Anda (`origin`):
   ```bash
   git add .
   git commit -m "feat: menambahkan halaman daftar pesanan aktif"
   git push -u origin feat/nama-fitur
   ```
5. **Buat Pull Request (PR)**:
   - Buka halaman repositori *fork* Anda di GitHub.
   - Klik tombol **"Compare & pull request"**.
   - Pastikan **base repository** mengarah ke repositori utama (`firdauzynuzula/fast_wash_project_based_learning` branch `master`) dan **head repository** adalah *fork* Anda (`feat/nama-fitur`).
   - Tuliskan deskripsi perubahan dengan jelas, lalu kirim Pull Request.

---

### 4. Sinkronisasi Fork (Sync Fork)

Jika repositori utama (`upstream`) mendapatkan pembaruan baru dari anggota tim lain, lakukan sinkronisasi branch `master` lokal Anda:

```bash
git fetch upstream
git checkout master
git merge upstream/master
git push origin master
```

Jika Anda sedang memiliki branch fitur aktif (`feat/nama-fitur`), gabungkan perubahan `master` terbaru ke branch fitur tersebut:
```bash
git checkout feat/nama-fitur
git merge master
```

---

### 5. Standar & Aturan Pengembangan

- Seluruh endpoint halaman baru wajib didaftarkan pada array `$routes` di [`index.php`](file:///E:/fast-wash-pbl/index.php).
- Pastikan selalu menggunakan fungsi `e()` dari [`includes/helpers.php`](file:///E:/fast-wash-pbl/includes/helpers.php) saat mencetak variabel ke HTML untuk mencegah kerentanan XSS.
- Gunakan fungsi `asset_url()` untuk memanggil seluruh file CSS dan JS agar terbebas dari masalah caching.
- Lindungi form penting (seperti pendaftaran, login, dan aksi status) menggunakan token CSRF dari [`includes/csrf.php`](file:///E:/fast-wash-pbl/includes/csrf.php).
