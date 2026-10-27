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
