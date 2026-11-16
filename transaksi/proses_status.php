<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

$idPesanan    = (int) ($_POST['id_pesanan'] ?? 0);
$statusTujuan = trim($_POST['status_tujuan'] ?? '');

if ($idPesanan <= 0 || !in_array($statusTujuan, ['diterima', 'diproses', 'selesai', 'diambil'], true)) {
    $_SESSION['flash_error'] = 'Data perubahan status tidak valid.';
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

// Validasi urutan transisi: diterima -> diproses -> selesai -> diambil
$urutanValid = [
    'diterima' => 'diproses',
    'diproses' => 'selesai',
    'selesai'  => 'diambil',
];

if (!isset($urutanValid[$statusSaatIni]) || $urutanValid[$statusSaatIni] !== $statusTujuan) {
    $_SESSION['flash_error'] = "Transisi status dari '{$statusSaatIni}' ke '{$statusTujuan}' tidak diizinkan.";
    header('Location: ' . $base . 'pesanan/detail?id=' . $idPesanan);
    exit;
}

$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
// Pastikan karyawan valid di tabel karyawan
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
        'status' => $statusTujuan,
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
        'status'      => $statusTujuan,
    ]);

    $pdo->commit();

    $labelStatus = ucfirst($statusTujuan);
    $_SESSION['flash_sukses'] = "Status pesanan {$pesanan['kode_pesanan']} berhasil diubah menjadi '{$labelStatus}'.";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Gagal memperbarui status: ' . $e->getMessage();
}

header('Location: ' . $base . 'pesanan/detail?id=' . $idPesanan);
exit;
