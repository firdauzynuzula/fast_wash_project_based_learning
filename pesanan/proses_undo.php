<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

$idPesanan = (int) ($_POST['id_pesanan'] ?? 0);

function kembaliDetail(int $id, string $kunci, string $pesan): void
{
    global $base;
    $_SESSION[$kunci] = $pesan;
    header('Location: ' . $base . 'pesanan/detail?id=' . $id);
    exit;
}

if ($idPesanan <= 0) {
    $_SESSION['flash_error'] = 'Pesanan tidak valid.';
    header('Location: ' . $base . 'pesanan');
    exit;
}

<<<<<<< HEAD
// Ambil status saat ini
$stmt = $pdo->prepare("SELECT id_pesanan, kode_pesanan, status, status_bayar FROM pesanan WHERE id_pesanan = :id");
$stmt->execute(['id' => $idPesanan]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
    header('Location: ' . $base . 'pesanan');
    exit;
}

$statusSaatIni = $pesanan['status'];

// Logika Undo: mundur 1 langkah
$reverseMap = [
    'diambil'     => 'selesai',
    'selesai'     => 'diproses',
    'diproses'    => 'diterima',
    'dicuci'      => 'diterima',
    'dikeringkan' => 'diterima',
    'disetrika'   => 'diterima',
    'diterima'    => null, // tidak bisa undo dari Diterima
=======
// Alur mundur satu tahap
$prevMap = [
    'diterima' => null,
    'diproses' => 'diterima',
    'selesai'  => 'diproses',
    'diambil'  => 'selesai',
>>>>>>> 738207f5a561bc53f2efbf047ce20259c6fb5d77
];

$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$cekKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$cekKaryawan->execute(['id' => $idKaryawan]);
if (!$cekKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT status FROM pesanan WHERE id_pesanan = :id FOR UPDATE");
    $stmt->execute(['id' => $idPesanan]);
    $statusSekarang = $stmt->fetchColumn();

    if ($statusSekarang === false) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
        header('Location: ' . $base . 'pesanan');
        exit;
    }

    $statusSekarang = strtolower($statusSekarang);
    $statusSebelum  = $prevMap[$statusSekarang] ?? null;

    if ($statusSebelum === null) {
        $pdo->rollBack();
        kembaliDetail($idPesanan, 'flash_error', 'Status pesanan sudah berada di tahap awal.');
    }

    $upd = $pdo->prepare("UPDATE pesanan SET status = :status WHERE id_pesanan = :id");
    $upd->execute(['status' => $statusSebelum, 'id' => $idPesanan]);

    // Dicatat sebagai riwayat baru supaya jejak perubahan tetap utuh
    $riwayat = $pdo->prepare("
        INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
        VALUES (:id_pesanan, :id_karyawan, :status, NOW())
    ");
    $riwayat->execute([
        'id_pesanan'  => $idPesanan,
        'id_karyawan' => $idKaryawan,
        'status'      => $statusSebelum,
    ]);

    $pdo->commit();

    kembaliDetail($idPesanan, 'flash_sukses', 'Status dikembalikan ke ' . ucfirst($statusSebelum) . '.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    kembaliDetail($idPesanan, 'flash_error', 'Gagal mengembalikan status: ' . $e->getMessage());
}
