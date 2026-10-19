CREATE TABLE pelanggan (
    id      SERIAL PRIMARY KEY,
    nama    VARCHAR(100) NOT NULL,
    telepon VARCHAR(20),
    alamat  TEXT
);

CREATE TABLE layanan (
    id            SERIAL PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,   -- cth: Cuci Kering, Setrika, Cuci+Setrika
    harga_per_kg  NUMERIC(10,2) NOT NULL,
    estimasi_hari INT NOT NULL DEFAULT 2
);
