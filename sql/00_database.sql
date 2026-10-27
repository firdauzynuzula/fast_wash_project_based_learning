-- =====================================================================
-- FAST WASH - 00_DATABASE.SQL
-- Perintah Pembuatan Database PostgreSQL untuk Fast Wash PBL Kelompok 5
-- =====================================================================

-- CATATAN:
-- Perintah CREATE DATABASE tidak dapat dijalankan di dalam blok transaksi.
-- Jalankan perintah ini di database 'postgres' / default connection di DBeaver / psql:

-- 1. Buat Database Fast Wash
CREATE DATABASE fast_wash
    WITH 
    ENCODING = 'UTF8'
    LC_COLLATE = 'C'
    LC_CTYPE = 'C'
    TEMPLATE = template0;

-- 2. Beri Komentar Deskripsi Database
COMMENT ON DATABASE fast_wash IS 'Database Operasional Sistem Informasi Fast Wash - PBL Kelompok 5 D4 SIB 2C';

-- 3. Di psql CLI, hubungkan ke database baru dengan:
-- \connect fast_wash;
