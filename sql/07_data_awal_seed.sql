-- =====================================================================
-- FAST WASH - 07_DATA_AWAL_SEED.SQL
-- Data Awal / Seed Data Sesuai Prototype Figma & Dokumen Proposal Kelompok 5
-- Password default semua akun: password123
-- Hash Bcrypt: $2y$12$7AW2RfQtzAPClaI6NZSAA.1Tiich9gYavL1CJGIA1f5BWnCAWs7z6
-- =====================================================================

-- 1. SEED LAYANAN LAUNDRY
INSERT INTO layanan (nama_layanan, harga, satuan, durasi_jam, is_active) VALUES
    ('Cuci + Setrika', 15000.00, 'kg', 48, true),
    ('Cuci Kering',    12000.00, 'kg', 24, true),
    ('Setrika',         8000.00, 'kg', 24, true)
ON CONFLICT DO NOTHING;

-- 2. SEED PEMILIK (OWNER)
INSERT INTO pemilik (nama, no_telepon, email) VALUES
    ('Fassy (Areta Neila Putri)', '081234567800', 'fastwash@gmail.com')
ON CONFLICT DO NOTHING;

-- 3. SEED KARYAWAN (PETUGAS / KASIR)
INSERT INTO karyawan (nama, no_telepon, alamat, status_aktif) VALUES
    ('Reva Rosalina',              '081234567801', 'Jl. Sigura-gura No. 10, Malang', true),
    ('Firdauzy Nuzula Pramadhani', '081234567802', 'Jl. Soekarno Hatta No. 45, Malang', true),
    ('Muhammad Nayaka Alfarezel',  '081234567803', 'Jl. Candi Mendut No. 8, Malang', true)
ON CONFLICT (no_telepon) DO NOTHING;

-- 4. SEED PELANGGAN (DATA SESUAI FIGMA & TIM PROPOSAL)
INSERT INTO pelanggan (nama, no_telepon, alamat, tanggal_daftar) VALUES
    ('Alyssa Naura',        '081234567890', 'Jl. Melati No. 12, Malang',       NOW() - INTERVAL '15 days'),
    ('Sarah Amanda',        '081234567811', 'Jl. Mawar No. 4, Malang',         NOW() - INTERVAL '10 days'),
    ('Nadya Alya',          '081234567812', 'Jl. Kamboja No. 9, Malang',       NOW() - INTERVAL '7 days'),
    ('Areta Neila',         '081234567813', 'Jl. Bunga Coklat No. 2, Malang',  NOW() - INTERVAL '5 days'),
    ('Ayusha Marella',      '081234567814', 'Jl. Terusan Surabaya, Malang',    NOW() - INTERVAL '4 days'),
    ('Lucky Satria Utama',  '081234567815', 'Jl. Veteran No. 17, Malang',       NOW() - INTERVAL '2 days'),
    ('Muhammad Nayaka',     '081234567816', 'Jl. Candi Panggung No. 5, Malang', NOW() - INTERVAL '1 day')
ON CONFLICT (no_telepon) DO NOTHING;

-- 5. SEED PENGGUNA (AKUN LOGIN SISTEM)
-- Password: password123
INSERT INTO pengguna (id_pemilik, email, password_hash, role, is_active)
SELECT id_pemilik, 'fastwash@gmail.com', '$2y$12$7AW2RfQtzAPClaI6NZSAA.1Tiich9gYavL1CJGIA1f5BWnCAWs7z6', 'pemilik', true
FROM pemilik WHERE email = 'fastwash@gmail.com'
ON CONFLICT (email) DO NOTHING;

INSERT INTO pengguna (id_karyawan, email, password_hash, role, is_active)
SELECT id_karyawan, 'reva@fastwash.com', '$2y$12$7AW2RfQtzAPClaI6NZSAA.1Tiich9gYavL1CJGIA1f5BWnCAWs7z6', 'karyawan', true
FROM karyawan WHERE no_telepon = '081234567801'
ON CONFLICT (email) DO NOTHING;

INSERT INTO pengguna (id_pelanggan, email, password_hash, role, is_active)
SELECT id_pelanggan, 'alyssa@gmail.com', '$2y$12$7AW2RfQtzAPClaI6NZSAA.1Tiich9gYavL1CJGIA1f5BWnCAWs7z6', 'pelanggan', true
FROM pelanggan WHERE no_telepon = '081234567890'
ON CONFLICT (email) DO NOTHING;

-- 6. SEED PESANAN SAMPLE (MEREFLEKSIKAN FIGMA)
-- #FW24043: Alyssa Naura (Cuci + Setrika 4 kg, QRIS Lunas)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, waktu_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, '#FW24043', NOW() - INTERVAL '2 days', NOW() + INTERVAL '1 day', NOW() - INTERVAL '4 hours', 'selesai', 0, 'Pakaian katun & kemeja kerja, pisahkan kerah'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567890' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- #FW-234: Areta Neila (Dicuci, Belum Lunas)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, 'FW-234', NOW() - INTERVAL '1 day', NOW() + INTERVAL '1 day', 'dicuci', 0, 'Gamis & jilbab lembut'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567813' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- #FW-233: Ayusha Marella (Disetrika, Belum Lunas)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, 'FW-233', NOW() - INTERVAL '18 hours', NOW() + INTERVAL '12 hours', 'disetrika', 0, 'Celana bahan'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567814' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- #FW-232: Firdauzy Nuzula (Disetrika, Lunas Tunai)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, 'FW-232', NOW() - INTERVAL '20 hours', NOW() + INTERVAL '10 hours', 'disetrika', 0, 'Selimut tebal'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567811' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- #FW-231: Lucky Satria (Diambil, Lunas Transfer)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, waktu_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, 'FW-231', NOW() - INTERVAL '3 days', NOW() - INTERVAL '1 day', NOW() - INTERVAL '1 day', 'diambil', 10000, 'Layanan cepat 6 jam'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567815' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- #FW-230: Muhammad Nayaka (Diambil, Lunas QRIS)
INSERT INTO pesanan (id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan, estimasi_selesai, waktu_selesai, status, biaya_tambahan, catatan)
SELECT p.id_pelanggan, k.id_karyawan, 'FW-230', NOW() - INTERVAL '4 days', NOW() - INTERVAL '2 days', NOW() - INTERVAL '2 days', 'diambil', 0, 'Jaket hoodie'
FROM pelanggan p, karyawan k
WHERE p.no_telepon = '081234567816' AND k.no_telepon = '081234567801'
ON CONFLICT (kode_pesanan) DO NOTHING;

-- 7. SEED DETAIL PESANAN
-- Detail untuk #FW24043 (Cuci + Setrika 4.0 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 4.0, l.harga, 'Pewangi Ocean Fresh'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = '#FW24043' AND l.nama_layanan = 'Cuci + Setrika'
ON CONFLICT DO NOTHING;

-- Detail untuk #FW-234 (Cuci Kering 3.0 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 3.0, l.harga, 'Bahan halus'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = 'FW-234' AND l.nama_layanan = 'Cuci Kering'
ON CONFLICT DO NOTHING;

-- Detail untuk #FW-233 (Setrika 5.0 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 5.0, l.harga, 'Pewangi Lavender'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = 'FW-233' AND l.nama_layanan = 'Setrika'
ON CONFLICT DO NOTHING;

-- Detail untuk #FW-232 (Cuci + Setrika 3.5 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 3.5, l.harga, 'Standar'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = 'FW-232' AND l.nama_layanan = 'Cuci + Setrika'
ON CONFLICT DO NOTHING;

-- Detail untuk #FW-231 (Cuci + Setrika 5.0 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 5.0, l.harga, 'Express 6 Jam'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = 'FW-231' AND l.nama_layanan = 'Cuci + Setrika'
ON CONFLICT DO NOTHING;

-- Detail untuk #FW-230 (Cuci Kering 2.5 kg)
INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
SELECT p.id_pesanan, l.id_layanan, 2.5, l.harga, 'Kemasan plastik dobel'
FROM pesanan p, layanan l
WHERE p.kode_pesanan = 'FW-230' AND l.nama_layanan = 'Cuci Kering'
ON CONFLICT DO NOTHING;

-- 8. SEED RIWAYAT STATUS PESANAN (LOG PERKEMBANGAN CUCIAN)
INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
SELECT p.id_pesanan, p.id_karyawan, 'diterima', p.tanggal_pesan
FROM pesanan p;

INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
SELECT p.id_pesanan, p.id_karyawan, 'dicuci', p.tanggal_pesan + INTERVAL '2 hours'
FROM pesanan p WHERE p.status IN ('dicuci', 'dikeringkan', 'disetrika', 'selesai', 'diambil');

INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
SELECT p.id_pesanan, p.id_karyawan, 'disetrika', p.tanggal_pesan + INTERVAL '6 hours'
FROM pesanan p WHERE p.status IN ('disetrika', 'selesai', 'diambil');

INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
SELECT p.id_pesanan, p.id_karyawan, 'selesai', p.tanggal_pesan + INTERVAL '12 hours'
FROM pesanan p WHERE p.status IN ('selesai', 'diambil');

INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
SELECT p.id_pesanan, p.id_karyawan, 'diambil', p.tanggal_pesan + INTERVAL '24 hours'
FROM pesanan p WHERE p.status = 'diambil';

-- 9. SEED RIWAYAT TRANSAKSI PEMBAYARAN (VALID)
-- Transaksi QRIS untuk #FW24043 (Rp 60.000)
INSERT INTO riwayat_transaksi (id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi)
SELECT p.id_karyawan, p.id_pesanan, p.tanggal_pesan, p.total_harga, 'qris', 'valid'
FROM pesanan p WHERE p.kode_pesanan = '#FW24043';

-- Transaksi Tunai untuk #FW-232
INSERT INTO riwayat_transaksi (id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi)
SELECT p.id_karyawan, p.id_pesanan, p.tanggal_pesan + INTERVAL '1 hour', p.total_harga, 'tunai', 'valid'
FROM pesanan p WHERE p.kode_pesanan = 'FW-232';

-- Transaksi Transfer untuk #FW-231
INSERT INTO riwayat_transaksi (id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi)
SELECT p.id_karyawan, p.id_pesanan, p.tanggal_pesan + INTERVAL '2 hours', p.total_harga, 'transfer', 'valid'
FROM pesanan p WHERE p.kode_pesanan = 'FW-231';

-- Transaksi QRIS untuk #FW-230
INSERT INTO riwayat_transaksi (id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi)
SELECT p.id_karyawan, p.id_pesanan, p.tanggal_pesan, p.total_harga, 'qris', 'valid'
FROM pesanan p WHERE p.kode_pesanan = 'FW-230';

-- 10. SEED ABSENSI KARYAWAN
INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, jam_pulang, status)
SELECT id_karyawan, CURRENT_DATE, CURRENT_DATE + TIME '07:55:00', CURRENT_DATE + TIME '17:05:00', 'hadir'
FROM karyawan WHERE no_telepon = '081234567801'
ON CONFLICT (id_karyawan, tanggal) DO NOTHING;

INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, jam_pulang, status)
SELECT id_karyawan, CURRENT_DATE, CURRENT_DATE + TIME '08:15:00', NULL, 'terlambat'
FROM karyawan WHERE no_telepon = '081234567802'
ON CONFLICT (id_karyawan, tanggal) DO NOTHING;

-- 11. SEED LOG AKTIFITAS
INSERT INTO log_aktifitas (id_pengguna, id_pesanan, waktu, aksi, deskripsi)
SELECT u.id_pengguna, p.id_pesanan, p.tanggal_pesan, 'BUAT_PESANAN', 'Pesanan baru ' || p.kode_pesanan || ' dibuat oleh petugas'
FROM pengguna u, pesanan p
WHERE u.role = 'karyawan' AND p.kode_pesanan = '#FW24043'
LIMIT 1;

INSERT INTO log_aktifitas (id_pengguna, id_pesanan, waktu, aksi, deskripsi)
SELECT u.id_pengguna, p.id_pesanan, NOW(), 'PEMBAYARAN', 'Pembayaran QRIS lunas diterima untuk nota ' || p.kode_pesanan
FROM pengguna u, pesanan p
WHERE u.role = 'karyawan' AND p.kode_pesanan = '#FW24043'
LIMIT 1;
