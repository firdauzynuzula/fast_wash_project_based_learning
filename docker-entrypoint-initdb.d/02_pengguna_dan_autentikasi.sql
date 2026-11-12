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
