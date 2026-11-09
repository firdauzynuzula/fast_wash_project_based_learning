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
-- =====================================================================
-- FAST WASH - 02_PENGGUNA_DAN_AUTENTIKASI.SQL
-- Entitas Akun: Pengguna, Sesi Login, Reset Password
-- Sesuai ERD & Relasi One-to-One dengan Pelanggan/Karyawan/Pemilik
-- =====================================================================

-- 1. TABEL PENGGUNA
-- Relasi 1-to-1 opsional dengan pelanggan, karyawan, atau pemilik
CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna             SERIAL PRIMARY KEY,
    id_pelanggan            INTEGER UNIQUE REFERENCES pelanggan (id_pelanggan) ON DELETE SET NULL,
    id_pemilik              INTEGER UNIQUE REFERENCES pemilik (id_pemilik) ON DELETE SET NULL,
    id_karyawan             INTEGER UNIQUE REFERENCES karyawan (id_karyawan) ON DELETE SET NULL,
    email                   VARCHAR(100) UNIQUE,
    password_hash           VARCHAR(255) NOT NULL,
    role                    VARCHAR(20)  NOT NULL,
    is_active               BOOLEAN      NOT NULL DEFAULT TRUE,
    waktu_dibuat            TIMESTAMP    NOT NULL DEFAULT NOW(),
    waktu_setuju_ketentuan  TIMESTAMP,
    CONSTRAINT chk_pengguna_role
        CHECK (role IN ('pemilik', 'karyawan', 'pelanggan')),
    -- Memastikan tepat 1 FK identitas terisi sesuai dengan role (Exclusive Arc 1:1)
    CONSTRAINT chk_pengguna_identitas CHECK (
        (role = 'pelanggan' AND id_pelanggan IS NOT NULL AND id_karyawan IS NULL  AND id_pemilik IS NULL) OR
        (role = 'karyawan'  AND id_karyawan  IS NOT NULL AND id_pelanggan IS NULL AND id_pemilik IS NULL) OR
        (role = 'pemilik'   AND id_pemilik   IS NOT NULL AND id_pelanggan IS NULL AND id_karyawan IS NULL)
    )
);

COMMENT ON TABLE pengguna IS 'Akun kredensial sistem untuk login dengan 3 peran berbeda';
COMMENT ON CONSTRAINT chk_pengguna_identitas ON pengguna IS 'Menjamin tepat 1 relasi identitas terisi sesuai role (1:1)';

-- 2. TABEL SESI LOGIN
CREATE TABLE IF NOT EXISTS sesi_login (
    id_sesion           SERIAL PRIMARY KEY,
    id_pengguna         INTEGER      NOT NULL REFERENCES pengguna (id_pengguna) ON DELETE CASCADE,
    token_hash          VARCHAR(255) NOT NULL,
    waktu_login         TIMESTAMP    NOT NULL DEFAULT NOW(),
    waktu_logout        TIMESTAMP,
    waktu_kadaluarsa    TIMESTAMP    NOT NULL,
    ingat_saya          BOOLEAN      NOT NULL DEFAULT FALSE,
    CONSTRAINT chk_sesi_waktu CHECK (waktu_kadaluarsa > waktu_login)
);

COMMENT ON TABLE sesi_login IS 'Pencatatan sesi aktif login dan opsi remember me';

-- 3. TABEL RESET PASSWORD
CREATE TABLE IF NOT EXISTS reset_password (
    id_reset_password   SERIAL PRIMARY KEY,
    id_pengguna         INTEGER      NOT NULL REFERENCES pengguna (id_pengguna) ON DELETE CASCADE,
    token_hash          VARCHAR(255) NOT NULL,
    waktu_dibuat        TIMESTAMP    NOT NULL DEFAULT NOW(),
    waktu_kadaluarsa    TIMESTAMP    NOT NULL,
    waktu_dipakai       TIMESTAMP,
    CONSTRAINT chk_reset_waktu CHECK (waktu_kadaluarsa > waktu_dibuat)
);

COMMENT ON TABLE reset_password IS 'Token verifikasi saat pengguna meminta reset kata sandi';
-- =====================================================================
-- FAST WASH - 03_PESANAN_DAN_TRANSAKSI.SQL
-- Entitas Operasional Laundry: Pesanan, Detail Pesanan,
-- Riwayat Status Pesanan, Riwayat Transaksi (Append-Only)
-- =====================================================================

-- 1. TABEL PESANAN INDUK
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan          SERIAL PRIMARY KEY,
    id_pelanggan        INTEGER       NOT NULL REFERENCES pelanggan (id_pelanggan),
    id_karyawan         INTEGER       NOT NULL REFERENCES karyawan (id_karyawan),
    kode_pesanan        VARCHAR(30)   NOT NULL UNIQUE,
    tanggal_pesan       TIMESTAMP     NOT NULL DEFAULT NOW(),
    estimasi_selesai    TIMESTAMP,
    waktu_selesai       TIMESTAMP,
    status              VARCHAR(30)   NOT NULL DEFAULT 'diterima',
    subtotal_layanan    NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (subtotal_layanan >= 0),
    biaya_tambahan      NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (biaya_tambahan >= 0),
    total_harga         NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (total_harga >= 0),
    status_bayar        VARCHAR(20)   NOT NULL DEFAULT 'belum_lunas',
    catatan             VARCHAR(255),
    CONSTRAINT chk_pesanan_status
        CHECK (status IN ('pending', 'diterima', 'dicuci', 'dikeringkan', 'disetrika', 'selesai', 'diambil')),
    CONSTRAINT chk_pesanan_status_bayar
        CHECK (status_bayar IN ('belum_lunas', 'sebagian', 'lunas'))
);

COMMENT ON TABLE pesanan IS 'Pencatatan pesanan laundry utama, status progres, dan status pembayaran';
COMMENT ON COLUMN pesanan.status IS '6 tahap progres cucian utama: diterima, dicuci, dikeringkan, disetrika, selesai, diambil (ditambah pending untuk pesanan dibuat)';
COMMENT ON COLUMN pesanan.biaya_tambahan IS 'Biaya ekstra seperti Layanan Cepat / Antar Jemput';

-- 2. TABEL DETAIL PESANAN
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail_pesanan   SERIAL PRIMARY KEY,
    id_pesanan          INTEGER       NOT NULL REFERENCES pesanan (id_pesanan) ON DELETE CASCADE,
    id_layanan          INTEGER       NOT NULL REFERENCES layanan (id_layanan),
    qty                 NUMERIC(6,2)  NOT NULL CHECK (qty > 0),
    harga_satuan        NUMERIC(12,2) NOT NULL CHECK (harga_satuan >= 0),
    subtotal            NUMERIC(12,2) GENERATED ALWAYS AS (ROUND(qty * harga_satuan, 2)) STORED,
    catatan             VARCHAR(255)
);

COMMENT ON TABLE detail_pesanan IS 'Rincian item layanan dalam satu pesanan (mendukung multi item / layanan)';

-- 3. TABEL RIWAYAT STATUS PESANAN
CREATE TABLE IF NOT EXISTS riwayat_status_pesanan (
    id_riwayat_status_pesanan SERIAL PRIMARY KEY,
    id_pesanan          INTEGER     NOT NULL REFERENCES pesanan (id_pesanan) ON DELETE CASCADE,
    id_karyawan         INTEGER     NOT NULL REFERENCES karyawan (id_karyawan),
    status              VARCHAR(30) NOT NULL,
    waktu               TIMESTAMP   NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_riwayat_status
        CHECK (status IN ('pending', 'diterima', 'dicuci', 'dikeringkan', 'disetrika', 'selesai', 'diambil'))
);

COMMENT ON TABLE riwayat_status_pesanan IS 'Log perubahan tahapan pengerjaan cucian dari waktu ke waktu oleh karyawan';

-- 4. TABEL RIWAYAT TRANSAKSI (APPEND-ONLY)
CREATE TABLE IF NOT EXISTS riwayat_transaksi (
    id_transaksi        SERIAL PRIMARY KEY,
    id_karyawan         INTEGER       REFERENCES karyawan (id_karyawan),
    id_pesanan          INTEGER       NOT NULL REFERENCES pesanan (id_pesanan),
    tanggal_bayar       TIMESTAMP     NOT NULL DEFAULT NOW(),
    jumlah_bayar        NUMERIC(12,2) NOT NULL CHECK (jumlah_bayar > 0),
    jenis_pembayaran    VARCHAR(20)   NOT NULL,
    status_transaksi    VARCHAR(10)   NOT NULL DEFAULT 'valid',
    CONSTRAINT chk_transaksi_jenis
        CHECK (jenis_pembayaran IN ('tunai', 'qris', 'transfer')),
    CONSTRAINT chk_transaksi_status
        CHECK (status_transaksi IN ('valid', 'void'))
);

COMMENT ON TABLE riwayat_transaksi IS 'Pencatatan pembayaran transaksi bersifat Append-Only. Koreksi hanya melalui mekanisme Void';
COMMENT ON COLUMN riwayat_transaksi.jenis_pembayaran IS 'Metode pembayaran di UI: tunai, qris, transfer';
COMMENT ON COLUMN riwayat_transaksi.status_transaksi IS 'valid = sah/tercatat kas, void = dibatalkan resmi';
-- =====================================================================
-- FAST WASH - 04_ABSENSI_DAN_LOG.SQL
-- Entitas SDM & Audit Trail: Absensi Karyawan, Log Aktifitas
-- =====================================================================

-- 1. TABEL ABSENSI KARYAWAN
CREATE TABLE IF NOT EXISTS absensi (
    id_absensi      SERIAL PRIMARY KEY,
    id_karyawan     INTEGER     NOT NULL REFERENCES karyawan (id_karyawan),
    tanggal         DATE        NOT NULL DEFAULT CURRENT_DATE,
    jam_masuk       TIMESTAMP   NOT NULL DEFAULT NOW(),
    jam_pulang      TIMESTAMP,
    status          VARCHAR(30) NOT NULL DEFAULT 'hadir',
    CONSTRAINT uq_absensi_karyawan_tanggal UNIQUE (id_karyawan, tanggal),
    CONSTRAINT chk_absensi_status
        CHECK (status IN ('hadir', 'terlambat', 'izin', 'sakit', 'alpha')),
    CONSTRAINT chk_absensi_jam CHECK (jam_pulang IS NULL OR jam_pulang >= jam_masuk)
);

COMMENT ON TABLE absensi IS 'Rekapitulasi kehadiran harian karyawan (check-in / check-out mandiri)';

-- 2. TABEL LOG AKTIFITAS
CREATE TABLE IF NOT EXISTS log_aktifitas (
    id_log          SERIAL PRIMARY KEY,
    id_pengguna     INTEGER      NOT NULL REFERENCES pengguna (id_pengguna),
    id_pesanan      INTEGER      REFERENCES pesanan (id_pesanan),
    waktu           TIMESTAMP    NOT NULL DEFAULT NOW(),
    aksi            VARCHAR(50)  NOT NULL,
    deskripsi       TEXT         NOT NULL
);

COMMENT ON TABLE log_aktifitas IS 'Audit trail jejak aktivitas pengguna sistem (immutable / tidak dapat diubah)';
-- =====================================================================
-- FAST WASH - 05_INDEKS_DAN_TRIGGER.SQL
-- Indeks Performa & Aturan Bisnis Terotomatisasi (Trigger & Function)
-- =====================================================================

-- =====================================================================
-- 1. INDEKS OPTIMASI PENCARIAN & JOIN
-- =====================================================================
CREATE INDEX IF NOT EXISTS idx_pelanggan_tanggal_daftar ON pelanggan (tanggal_daftar);
CREATE INDEX IF NOT EXISTS idx_sesi_login_pengguna      ON sesi_login (id_pengguna);
CREATE INDEX IF NOT EXISTS idx_reset_password_pengguna  ON reset_password (id_pengguna);
CREATE INDEX IF NOT EXISTS idx_pesanan_pelanggan        ON pesanan (id_pelanggan);
CREATE INDEX IF NOT EXISTS idx_pesanan_karyawan         ON pesanan (id_karyawan);
CREATE INDEX IF NOT EXISTS idx_pesanan_status           ON pesanan (status);
CREATE INDEX IF NOT EXISTS idx_pesanan_status_bayar     ON pesanan (status_bayar);
CREATE INDEX IF NOT EXISTS idx_pesanan_tanggal_pesan    ON pesanan (tanggal_pesan);
CREATE INDEX IF NOT EXISTS idx_detail_pesanan_pesanan   ON detail_pesanan (id_pesanan);
CREATE INDEX IF NOT EXISTS idx_detail_pesanan_layanan   ON detail_pesanan (id_layanan);
CREATE INDEX IF NOT EXISTS idx_riwayat_status_pesanan   ON riwayat_status_pesanan (id_pesanan);
CREATE INDEX IF NOT EXISTS idx_transaksi_pesanan        ON riwayat_transaksi (id_pesanan);
CREATE INDEX IF NOT EXISTS idx_transaksi_tanggal_bayar  ON riwayat_transaksi (tanggal_bayar);
CREATE INDEX IF NOT EXISTS idx_log_waktu                ON log_aktifitas (waktu DESC);
CREATE INDEX IF NOT EXISTS idx_log_pengguna             ON log_aktifitas (id_pengguna);
CREATE INDEX IF NOT EXISTS idx_log_pesanan              ON log_aktifitas (id_pesanan);

-- =====================================================================
-- 2. FUNGSI HITUNG STATUS BAYAR DARI TRANSAKSI VALID
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_status_bayar(p_id_pesanan INTEGER, p_total NUMERIC)
RETURNS VARCHAR AS $$
    SELECT CASE
             WHEN s.bayar <= 0 THEN 'belum_lunas'
             WHEN p_total > 0 AND s.bayar >= p_total THEN 'lunas'
             ELSE 'sebagian'
           END
      FROM (SELECT COALESCE(SUM(jumlah_bayar), 0) AS bayar
              FROM riwayat_transaksi
             WHERE id_pesanan = p_id_pesanan AND status_transaksi = 'valid') s;
$$ LANGUAGE sql STABLE;

-- =====================================================================
-- 3. TRIGGER PENGISIAN HARGA SATUAN DETAIL PESANAN OTOMATIS
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_isi_detail_pesanan() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.harga_satuan IS NULL THEN
        SELECT harga INTO NEW.harga_satuan FROM layanan WHERE id_layanan = NEW.id_layanan;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_detail_isi ON detail_pesanan;
CREATE TRIGGER trg_detail_isi
BEFORE INSERT ON detail_pesanan
FOR EACH ROW EXECUTE FUNCTION fn_isi_detail_pesanan();

-- =====================================================================
-- 4. TRIGGER HITUNG SUBTOTAL & ESTIMASI SELESAI PADA PESANAN
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_hitung_total_pesanan() RETURNS TRIGGER AS $$
DECLARE
    v_id INTEGER;
BEGIN
    v_id := COALESCE(NEW.id_pesanan, OLD.id_pesanan);
    UPDATE pesanan p
       SET subtotal_layanan = COALESCE((SELECT SUM(d.subtotal)
                                          FROM detail_pesanan d
                                         WHERE d.id_pesanan = v_id), 0),
           estimasi_selesai = COALESCE(p.estimasi_selesai,
                (SELECT p.tanggal_pesan + MAKE_INTERVAL(hours => MAX(l.durasi_jam))
                   FROM detail_pesanan d
                   JOIN layanan l ON l.id_layanan = d.id_layanan
                  WHERE d.id_pesanan = v_id))
     WHERE p.id_pesanan = v_id;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_detail_hitung_total ON detail_pesanan;
CREATE TRIGGER trg_detail_hitung_total
AFTER INSERT OR UPDATE OR DELETE ON detail_pesanan
FOR EACH ROW EXECUTE FUNCTION fn_hitung_total_pesanan();

-- =====================================================================
-- 5. TRIGGER TOTAL HARGA & STATUS SELESAI PADA PESANAN
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_pesanan_hitung() RETURNS TRIGGER AS $$
BEGIN
    NEW.total_harga := NEW.subtotal_layanan + NEW.biaya_tambahan;
    IF TG_OP = 'UPDATE' THEN
        NEW.status_bayar := fn_status_bayar(NEW.id_pesanan, NEW.total_harga);
        IF NEW.status = 'selesai' AND OLD.status <> 'selesai' AND NEW.waktu_selesai IS NULL THEN
            NEW.waktu_selesai := NOW();
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_pesanan_hitung ON pesanan;
CREATE TRIGGER trg_pesanan_hitung
BEFORE INSERT OR UPDATE ON pesanan
FOR EACH ROW EXECUTE FUNCTION fn_pesanan_hitung();

-- =====================================================================
-- 6. TRIGGER SINKRONISASI STATUS BAYAR SETELAH TRANSAKSI
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_sinkron_status_bayar() RETURNS TRIGGER AS $$
BEGIN
    UPDATE pesanan
       SET status_bayar = fn_status_bayar(id_pesanan, total_harga)
     WHERE id_pesanan = NEW.id_pesanan;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_transaksi_sinkron_bayar ON riwayat_transaksi;
CREATE TRIGGER trg_transaksi_sinkron_bayar
AFTER INSERT OR UPDATE ON riwayat_transaksi
FOR EACH ROW EXECUTE FUNCTION fn_sinkron_status_bayar();

-- =====================================================================
-- 7. ATURAN APPEND-ONLY TRANSAKSI (HANYA BOLEH DI-VOID, TIDAK BISA DIUBAH / DIHAPUS)
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_jaga_transaksi() RETURNS TRIGGER AS $$
BEGIN
    IF (NEW.id_pesanan, NEW.id_karyawan, NEW.tanggal_bayar, NEW.jumlah_bayar, NEW.jenis_pembayaran)
       IS DISTINCT FROM
       (OLD.id_pesanan, OLD.id_karyawan, OLD.tanggal_bayar, OLD.jumlah_bayar, OLD.jenis_pembayaran) THEN
        RAISE EXCEPTION 'Data transaksi pembayaran tidak boleh diubah langsung demi integritas pembukuan kas. Gunakan mekanisme status void untuk pembatalan.';
    END IF;
    IF OLD.status_transaksi = 'void' AND NEW.status_transaksi = 'valid' THEN
        RAISE EXCEPTION 'Transaksi yang sudah berstatus void tidak dapat diaktifkan kembali.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_transaksi_jaga ON riwayat_transaksi;
CREATE TRIGGER trg_transaksi_jaga
BEFORE UPDATE ON riwayat_transaksi
FOR EACH ROW EXECUTE FUNCTION fn_jaga_transaksi();

CREATE OR REPLACE FUNCTION fn_blokir_hapus_transaksi() RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Tabel riwayat_transaksi bersifat append-only. Dilarang menghapus rekaman pembayaran. Gunakan mekanisme Void (status_transaksi = ''void'') untuk pembatalan.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_transaksi_blokir_hapus ON riwayat_transaksi;
CREATE TRIGGER trg_transaksi_blokir_hapus
BEFORE DELETE ON riwayat_transaksi
FOR EACH ROW EXECUTE FUNCTION fn_blokir_hapus_transaksi();

-- =====================================================================
-- 8. ATURAN IMMUTABILITY LOG AKTIFITAS
-- =====================================================================
CREATE OR REPLACE FUNCTION fn_blokir_ubah_log() RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Tabel log_aktifitas bersifat immutable (audit trail); tidak boleh diubah atau dihapus.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_log_blokir_ubah ON log_aktifitas;
CREATE TRIGGER trg_log_blokir_ubah
BEFORE UPDATE OR DELETE ON log_aktifitas
FOR EACH ROW EXECUTE FUNCTION fn_blokir_ubah_log();
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
