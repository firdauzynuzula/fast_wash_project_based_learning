<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

csrf_check();

$idPesanan = (int) ($_POST['id_pesanan'] ?? 0);
$metode    = strtolower(trim($_POST['metode'] ?? 'tunai'));
$redirect  = trim($_POST['redirect'] ?? '');

if (!in_array($metode, ['tunai', 'qris', 'transfer'], true)) {
    $metode = 'tunai';
}

$stmt = $pdo->prepare("SELECT id_pesanan, kode_pesanan, total_harga, status_bayar FROM pesanan WHERE id_pesanan = :id");
$stmt->execute(['id' => $idPesanan]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

if ($pesanan['status_bayar'] === 'lunas') {
    $_SESSION['flash_sukses'] = "Pesanan {$pesanan['kode_pesanan']} sudah berstatus Lunas.";
    $target = $redirect ? ($base . $redirect) : ($base . 'transaksi/status');
    header('Location: ' . $target);
    exit;
}

$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$checkKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$checkKaryawan->execute(['id' => $idKaryawan]);
if (!$checkKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

try {
    // Catat ke riwayat_transaksi
    // Trigger trg_transaksi_sinkron_bayar di PostgreSQL akan otomatis mengupdate status_bayar di tabel pesanan menjadi 'lunas'
    $stmtBayar = $pdo->prepare("
        INSERT INTO riwayat_transaksi (
            id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi
        ) VALUES (
            :id_karyawan, :id_pesanan, NOW(), :jumlah_bayar, :jenis_pembayaran, 'valid'
        )
    ");
    $stmtBayar->execute([
        'id_karyawan'      => $idKaryawan,
        'id_pesanan'       => $idPesanan,
        'jumlah_bayar'     => $pesanan['total_harga'],
        'jenis_pembayaran' => $metode,
    ]);

    $_SESSION['flash_sukses'] = "Pembayaran pesanan {$pesanan['kode_pesanan']} sebesar Rp " . number_format((float) $pesanan['total_harga'], 0, ',', '.') . " (" . strtoupper($metode) . ") berhasil dikonfirmasi LUNAS.";

} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Gagal mencatat pembayaran: ' . $e->getMessage();
}

$target = $redirect ? ($base . $redirect) : ($base . 'transaksi/status');
header('Location: ' . $target);
exit;
