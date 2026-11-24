<?php
/**
 * Fast Wash - Penyimpanan sementara data layanan (berbasis session).
 *
 * Dipakai supaya alur tambah / edit / hapus bisa langsung dicoba tanpa database.
 * Kalau tabel layanan sudah siap, GANTI isi fungsi ini dengan query PDO
 * (SELECT) dan ganti manipulasi $_SESSION di proses.php dengan INSERT / UPDATE / DELETE.
 * File ini hanya berisi fungsi, tidak mencetak apa pun.
 */

function layananSemua(): array
{
    if (!isset($_SESSION['layanan'])) {
        $_SESSION['layanan'] = [
            ['id' => 1, 'nama' => 'Cuci + Setrika', 'deskripsi' => 'Pakaian dicuci dan disetrika rapi', 'harga' => 15000, 'aktif' => true],
            ['id' => 2, 'nama' => 'Cuci Kering',    'deskripsi' => 'Pakaian dicuci dan dikeringkan', 'harga' => 12000, 'aktif' => true],
            ['id' => 3, 'nama' => 'Setrika',        'deskripsi' => 'Pakaian disetrika hingga rapi', 'harga' => 8000,  'aktif' => true],
        ];
        $_SESSION['layanan_next_id'] = 4;
    }
    return $_SESSION['layanan'];
}