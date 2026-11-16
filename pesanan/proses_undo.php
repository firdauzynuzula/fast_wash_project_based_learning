<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

$idPesanan    = (int) ($_POST['id_pesanan'] ?? 0);

if ($idPesanan <= 0) {
    $_SESSION['flash_error'] = 'Data pesanan tidak valid.';
    header('Location: ' . $base . 'pesanan');
    exit;
}

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
];

$previousStatus = $reverseMap[$statusSaatIni] ?? null;

if (!$previousStatus) {
    $_SESSION['flash_error'] = 'Tidak dapat mengembalikan status dari tahap awal (Diterima).';
    header('Location: ' . $base . 'pesanan/detail?id=' . $idPesanan);
    exit;
}

// Pastikan karyawan valid
$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$checkKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$checkKaryawan->execute(['id' => $idKaryawan]);
if (!$checkKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

try {
    $pdo->beginTransaction();

    // Update status pesanan
    $stmtUpdate = $pdo->prepare("
        UPDATE pesanan
           SET status = :status,
               waktu_selesai = (CASE WHEN :status = 'selesai' AND waktu_selesai IS NULL THEN NOW() ELSE waktu_selesai END)
         WHERE id_pesanan = :id
    ");
    $stmtUpdate->execute([
        'status' => $previousStatus,
        'id'     => $idPesanan,
    ]);

    // Catat log riwayat
    $stmtRiwayat = $pdo->prepare("
        INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
        VALUES (:id_pesanan, :id_karyawan, :status, NOW())
    ");
    $stmtRiwayat->execute([
        'id_pesanan'  => $idPesanan,
        'id_karyawan' => $idKaryawan,
        'status'      => $previousStatus,
    ]);

    $pdo->commit();

    $labelPrevious = ucfirst($previousStatus);
    $_SESSION['flash_sukses'] = "Status pesanan {$pesanan['kode_pesanan']} berhasil dikembalikan ke '{$labelPrevious}'.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Gagal mengembalikan status: ' . $e->getMessage();
}

header('Location: ' . $base . 'pesanan/detail?id=' . $idPesanan);
exit;
