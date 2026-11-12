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
