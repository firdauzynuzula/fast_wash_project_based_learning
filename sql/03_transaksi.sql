CREATE TABLE transaksi (
    id              SERIAL PRIMARY KEY,
    pelanggan_id    INT NOT NULL REFERENCES pelanggan(id),
    layanan_id      INT NOT NULL REFERENCES layanan(id),
    berat_kg        NUMERIC(6,2) NOT NULL,
    total_harga     NUMERIC(12,2) NOT NULL,
    status          VARCHAR(10) NOT NULL DEFAULT 'baru'
                    CHECK (status IN ('baru','proses','selesai','diambil')),
    tanggal_masuk   TIMESTAMP NOT NULL DEFAULT now(),
    tanggal_selesai TIMESTAMP,
    tanggal_diambil TIMESTAMP
);
