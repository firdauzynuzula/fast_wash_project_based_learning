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
