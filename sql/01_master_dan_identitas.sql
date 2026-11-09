-- Active: 1791336200846@@127.0.0.1@5432@fast_wash
-- =====================================================================
-- FAST WASH - 01_MASTER_DAN_IDENTITAS.SQL
-- Entitas Master: Pelanggan, Pemilik, Karyawan, Layanan
-- Sesuai ERD & Dokumen Proposal PBL Kelompok 5
-- =====================================================================

-- 1. TABEL PELANGGAN
-- Menyimpan data profil pelanggan laundry
CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan    SERIAL PRIMARY KEY,
    nama            VARCHAR(100) NOT NULL,
    no_telepon      VARCHAR(20)  NOT NULL UNIQUE,
    alamat          VARCHAR(255),
    tanggal_daftar  TIMESTAMP    NOT NULL DEFAULT NOW()
);

COMMENT ON TABLE pelanggan IS 'Menyimpan profil pelanggan laundry';
COMMENT ON COLUMN pelanggan.nama IS 'Nama lengkap pelanggan';
COMMENT ON COLUMN pelanggan.no_telepon IS 'Nomor HP / WhatsApp pelanggan';
COMMENT ON COLUMN pelanggan.alamat IS 'Alamat domisili pelanggan';
COMMENT ON COLUMN pelanggan.tanggal_daftar IS 'Waktu registrasi pertama kali';

-- 2. TABEL PEMILIK
-- Data profil pemilik usaha (Owner)
CREATE TABLE IF NOT EXISTS pemilik (
    id_pemilik      SERIAL PRIMARY KEY,
    nama            VARCHAR(100) NOT NULL,
    no_telepon      VARCHAR(20)  NOT NULL,
    email           VARCHAR(100)
);

COMMENT ON TABLE pemilik IS 'Profil data pemilik usaha laundry (Owner)';

-- 3. TABEL KARYAWAN
-- Data profil karyawan operasional & kasir
CREATE TABLE IF NOT EXISTS karyawan (
    id_karyawan     SERIAL PRIMARY KEY,
    nama            VARCHAR(100) NOT NULL,
    no_telepon      VARCHAR(20)  NOT NULL UNIQUE,
    alamat          VARCHAR(255),
    status_aktif    BOOLEAN      NOT NULL DEFAULT TRUE
);

COMMENT ON TABLE karyawan IS 'Profil data karyawan operasional / kasir laundry';
COMMENT ON COLUMN karyawan.status_aktif IS 'Status keaktifan bekerja (true = aktif, false = nonaktif)';

-- 4. TABEL LAYANAN
-- Katalog jenis layanan laundry, tarif per satuan, dan durasi estimasi
CREATE TABLE IF NOT EXISTS layanan (
    id_layanan      SERIAL PRIMARY KEY,
    nama_layanan    VARCHAR(100)  NOT NULL,
    harga           NUMERIC(12,2) NOT NULL CHECK (harga >= 0),
    satuan          VARCHAR(30)   NOT NULL DEFAULT 'kg',
    durasi_jam      INTEGER       NOT NULL CHECK (durasi_jam >= 0),
    is_active       BOOLEAN       NOT NULL DEFAULT TRUE
);

COMMENT ON TABLE layanan IS 'Katalog jenis layanan laundry, tarif satuan, dan estimasi pengerjaan';
COMMENT ON COLUMN layanan.durasi_jam IS 'Estimasi standar pengerjaan dalam satuan jam';
