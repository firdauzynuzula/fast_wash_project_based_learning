-- =====================================================================
-- FAST WASH - 06_VIEWS_ANTARMUKA.SQL
-- View Database Khusus Layar & Fitur Sesuai Desain Figma Fast Wash
-- =====================================================================

-- =====================================================================
-- 1. VIEW DASBOR: RINGKASAN METRIK HARI INI
-- Menyuplai 4 kartu KPI di Dasbor Pemilik & Karyawan
-- =====================================================================
CREATE OR REPLACE VIEW v_dasbor_hari_ini AS
SELECT
    (SELECT COUNT(*) FROM pesanan WHERE tanggal_pesan::DATE = CURRENT_DATE)        AS total_pesanan_hari_ini,
    (SELECT COUNT(*) FROM pesanan WHERE tanggal_pesan::DATE = CURRENT_DATE - 1)    AS total_pesanan_kemarin,
    (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM riwayat_transaksi
      WHERE status_transaksi = 'valid' AND tanggal_bayar::DATE = CURRENT_DATE)     AS pendapatan_hari_ini,
    (SELECT COALESCE(SUM(jumlah_bayar), 0) FROM riwayat_transaksi
      WHERE status_transaksi = 'valid' AND tanggal_bayar::DATE = CURRENT_DATE - 1) AS pendapatan_kemarin,
    (SELECT COUNT(DISTINCT id_pelanggan) FROM pesanan
      WHERE tanggal_pesan >= NOW() - INTERVAL '30 days')                           AS pelanggan_aktif,
    (SELECT COUNT(*) FROM pelanggan
      WHERE tanggal_daftar >= DATE_TRUNC('week', NOW()))                           AS pelanggan_baru_minggu_ini,
    (SELECT COUNT(*) FROM pesanan WHERE status = 'selesai')                        AS siap_diambil;

-- =====================================================================
-- 2. VIEW DASBOR: GRAFIK PENDAPATAN 7 HARI TERAKHIR
-- Menyuplai grafik garis pendapatan di dasbor
-- =====================================================================
CREATE OR REPLACE VIEW v_pendapatan_7_hari AS
SELECT d::DATE AS tanggal,
       COALESCE(SUM(t.jumlah_bayar), 0) AS pendapatan
  FROM GENERATE_SERIES(CURRENT_DATE - 6, CURRENT_DATE, INTERVAL '1 day') d
  LEFT JOIN riwayat_transaksi t
         ON t.status_transaksi = 'valid' AND t.tanggal_bayar::DATE = d::DATE
 GROUP BY d
 ORDER BY d;

-- =====================================================================
-- 3. VIEW DASBOR: DAFTAR PESANAN TERBARU
-- =====================================================================
CREATE OR REPLACE VIEW v_pesanan_terbaru AS
SELECT p.id_pesanan,
       p.kode_pesanan,
       pl.nama           AS nama_pelanggan,
       STRING_AGG(l.nama_layanan, ' + ' ORDER BY d.id_detail_pesanan) AS layanan,
       COALESCE(SUM(d.qty), 0) AS total_qty,
       p.status,
       p.tanggal_pesan
  FROM pesanan p
  JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
  LEFT JOIN detail_pesanan d ON d.id_pesanan = p.id_pesanan
  LEFT JOIN layanan l ON l.id_layanan = d.id_layanan
 GROUP BY p.id_pesanan, pl.nama
 ORDER BY p.tanggal_pesan DESC;

-- =====================================================================
-- 4. VIEW HALAMAN DAFTAR PESANAN
-- Sesuai tabel di Figma: Kode, Nama Pelanggan, Progres Cucian, Status Pembayaran, Aksi
-- =====================================================================
CREATE OR REPLACE VIEW v_daftar_pesanan AS
SELECT p.id_pesanan,
       p.kode_pesanan,
       pl.nama           AS nama_pelanggan,
       p.status          AS progres_cucian,
       p.status_bayar    AS status_pembayaran,
       p.total_harga,
       p.tanggal_pesan,
       p.estimasi_selesai,
       CASE 
           WHEN p.status = 'diambil' AND p.status_bayar = 'lunas' THEN 'lihat_nota' 
           ELSE 'update' 
       END AS aksi_rekomendasi
  FROM pesanan p
  JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
 ORDER BY p.tanggal_pesan DESC;

-- =====================================================================
-- 5. VIEW HALAMAN DAFTAR PELANGGAN & STATISTIK
-- Menampilkan rekap order, pengeluaran, dan kartu ringkasan
-- =====================================================================
CREATE OR REPLACE VIEW v_rekap_pelanggan AS
SELECT pl.id_pelanggan,
       pl.nama           AS nama_pelanggan,
       pl.no_telepon,
       pl.alamat,
       pl.tanggal_daftar,
       (SELECT COUNT(*) FROM pesanan p
         WHERE p.id_pelanggan = pl.id_pelanggan) AS total_pesanan,
       (SELECT COALESCE(SUM(t.jumlah_bayar), 0)
          FROM riwayat_transaksi t
          JOIN pesanan p ON p.id_pesanan = t.id_pesanan
         WHERE p.id_pelanggan = pl.id_pelanggan
           AND t.status_transaksi = 'valid')      AS total_pengeluaran
  FROM pelanggan pl;

CREATE OR REPLACE VIEW v_statistik_pelanggan AS
SELECT (SELECT COUNT(*) FROM pelanggan) AS total_pelanggan,
       (SELECT COUNT(DISTINCT id_pelanggan) FROM pesanan
         WHERE tanggal_pesan >= NOW() - INTERVAL '30 days') AS pelanggan_aktif,
       (SELECT COUNT(*) FROM pelanggan
         WHERE tanggal_daftar >= DATE_TRUNC('month', NOW())) AS pelanggan_baru_bulan_ini,
       (SELECT ROUND(COUNT(*)::NUMERIC / NULLIF((SELECT COUNT(*) FROM pelanggan), 0), 1)
          FROM pesanan) AS rata_rata_order;

-- =====================================================================
-- 6. VIEW CETAK NOTA PESANAN (HEADER & ITEM)
-- Sesuai tampilan Nota Fisik & Digital di Figma (#FW24043)
-- =====================================================================
CREATE OR REPLACE VIEW v_nota_header AS
SELECT p.id_pesanan,
       p.kode_pesanan,
       p.tanggal_pesan   AS tanggal_masuk,
       p.estimasi_selesai,
       p.waktu_selesai,
       pl.nama           AS nama_pelanggan,
       pl.no_telepon     AS no_whatsapp_pelanggan,
       k.nama            AS kasir,
       p.subtotal_layanan,
       p.biaya_tambahan,
       p.total_harga,
       p.status          AS status_cucian,
       p.status_bayar,
       p.catatan,
       t.jenis_pembayaran AS metode_pembayaran,
       t.tanggal_bayar
  FROM pesanan p
  JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
  JOIN karyawan k   ON k.id_karyawan   = p.id_karyawan
  LEFT JOIN LATERAL (
        SELECT jenis_pembayaran, tanggal_bayar
          FROM riwayat_transaksi x
         WHERE x.id_pesanan = p.id_pesanan AND x.status_transaksi = 'valid'
         ORDER BY x.tanggal_bayar DESC, x.id_transaksi DESC
         LIMIT 1) t ON TRUE;

CREATE OR REPLACE VIEW v_nota_item AS
SELECT d.id_detail_pesanan,
       d.id_pesanan,
       l.nama_layanan,
       d.qty,
       l.satuan,
       d.harga_satuan,
       d.subtotal,
       d.catatan
  FROM detail_pesanan d
  JOIN layanan l ON l.id_layanan = d.id_layanan
 ORDER BY d.id_detail_pesanan;

-- =====================================================================
-- 7. VIEW AUDIT TRAIL LOG AKTIFITAS
-- =====================================================================
CREATE OR REPLACE VIEW v_log_aktifitas AS
SELECT l.id_log,
       l.waktu,
       COALESCE(pl.nama, k.nama, o.nama) AS nama_pengguna,
       u.role,
       l.aksi,
       l.deskripsi,
       l.id_pesanan,
       p.kode_pesanan
  FROM log_aktifitas l
  JOIN pengguna u        ON u.id_pengguna  = l.id_pengguna
  LEFT JOIN pelanggan pl ON pl.id_pelanggan = u.id_pelanggan
  LEFT JOIN karyawan k   ON k.id_karyawan   = u.id_karyawan
  LEFT JOIN pemilik o    ON o.id_pemilik    = u.id_pemilik
  LEFT JOIN pesanan p    ON p.id_pesanan    = l.id_pesanan
 ORDER BY l.waktu DESC;

-- =====================================================================
-- 8. VIEW LAPORAN PEMILIK (KEUANGAN, REKAP STATUS, & ABSENSI)
-- =====================================================================
CREATE OR REPLACE VIEW v_laporan_pendapatan_harian AS
SELECT tanggal_bayar::DATE  AS tanggal,
       COUNT(*)             AS jumlah_transaksi,
       SUM(jumlah_bayar)    AS total_pendapatan
  FROM riwayat_transaksi
 WHERE status_transaksi = 'valid'
 GROUP BY tanggal_bayar::DATE
 ORDER BY tanggal DESC;

CREATE OR REPLACE VIEW v_rekap_pesanan_per_status AS
SELECT status,
       COUNT(*)          AS jumlah_pesanan,
       SUM(total_harga)  AS total_nilai
  FROM pesanan
 GROUP BY status;

CREATE OR REPLACE VIEW v_laporan_absensi AS
SELECT a.id_absensi,
       a.tanggal,
       k.nama            AS nama_karyawan,
       a.jam_masuk,
       a.jam_pulang,
       a.status
  FROM absensi a
  JOIN karyawan k ON k.id_karyawan = a.id_karyawan
 ORDER BY a.tanggal DESC, k.nama;

-- =====================================================================
-- 9. VIEW AUTENTIKASI MULTI-IDENTITAS (LOGIN EMAIL ATAU NO HP)
-- =====================================================================
CREATE OR REPLACE VIEW v_login_identitas AS
SELECT u.id_pengguna, u.email, pl.no_telepon, u.password_hash, u.role, u.is_active, pl.nama
  FROM pengguna u JOIN pelanggan pl ON pl.id_pelanggan = u.id_pelanggan
UNION ALL
SELECT u.id_pengguna, u.email, k.no_telepon, u.password_hash, u.role, u.is_active, k.nama
  FROM pengguna u JOIN karyawan k ON k.id_karyawan = u.id_karyawan
UNION ALL
SELECT u.id_pengguna, u.email, o.no_telepon, u.password_hash, u.role, u.is_active, o.nama
  FROM pengguna u JOIN pemilik o ON o.id_pemilik = u.id_pemilik;
