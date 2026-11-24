# Fast Wash Database Migrations

Sistem manajemen migrasi basis data PostgreSQL untuk Fast Wash PBL. Dibangun secara mandiri (native) tanpa dependensi eksternal, memanfaatkan PDO PostgreSQL yang sudah ada.

---

## 🚀 Perintah Dasar

Semua perintah dijalankan melalui terminal di root proyek:

### 1. Menjalankan Migrasi Baru
```bash
php migrate.php
# atau
composer run migrate
```
Menjalankan seluruh berkas `.sql` yang berstatus `PENDING` secara berurutan. Setiap berkas dijalankan di dalam transaksi database yang aman (`BEGIN ... COMMIT`).

### 2. Melihat Status Migrasi
```bash
php migrate.php status
# atau
composer run migrate:status
```
Menampilkan tabel riwayat migrasi (`APPLIED` vs `PENDING`), nomor batch, dan tanggal eksekusi.

### 3. Membuat Migrasi Baru
```bash
php migrate.php create nama_migrasi
```
*Contoh:* `php migrate.php create tambah_kolom_poin_pelanggan`  
Akan membuat berkas ber-timestamp baru: `database/migrations/YYYYMMDD_HHMMSS_tambah_kolom_poin_pelanggan.sql`.

### 4. Baseline (Database yang Sudah Berisi Data)
```bash
php migrate.php baseline
```
Menandai semua berkas migrasi saat ini sebagai `[APPLIED]` tanpa mengeksekusi kueri SQL-nya. Digunakan jika database lokal sudah memiliki tabel lengkap agar tidak terjadi konflik/duplikasi.

---

## 👥 Alur Kerja Kolaborasi Tim

1. **Membuat Perubahan Database**:
   - Jalankan `php migrate.php create <nama_perubahan>`.
   - Buka berkas SQL yang baru dibuat di `database/migrations/`.
   - Tuliskan kueri `ALTER TABLE` atau `CREATE TABLE` yang diinginkan.
   - Uji coba dengan `php migrate.php`.
   - Commit berkas migrasi tersebut ke Git (`git add database/migrations/...`).

2. **Menerima Perubahan dari Rekan Tim**:
   - Tarik update dari GitHub (`git pull`).
   - Cukup jalankan `php migrate.php`.
   - Database lokal langsung terbarui tanpa perlu menghapus database dari nol atau membuka pgAdmin secara manual.
