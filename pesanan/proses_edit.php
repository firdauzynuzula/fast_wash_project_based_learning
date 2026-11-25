<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

$idPesanan    = (int) ($_POST['id_pesanan'] ?? 0);
$statusTujuan = strtolower(trim($_POST['status_tujuan'] ?? ''));

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

// Ambil status saat ini
$stmt = $pdo->prepare("SELECT id_pesanan, kode_pesanan, status, status_bayar, id_pelanggan, tanggal_pesan FROM pesanan WHERE id_pesanan = :id");
$stmt->execute(['id' => $idPesanan]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
    header('Location: ' . $base . 'pesanan');
    exit;
}

$statusSaatIni = $pesanan['status'];

// Validasi urutan transisi: diterima -> diproses -> selesai -> diambil
// Mendukung transisi dari status lama/detail: dicuci/dikeringkan/disetrika -> selesai
$urutanValid = [
    'diterima'    => 'diproses',
    'diproses'    => 'selesai',
    'dicuci'      => 'selesai',
    'dikeringkan' => 'selesai',
    'disetrika'   => 'selesai',
    'selesai'     => 'diambil',
];

$nextMap = [
    'diterima'    => 'diproses',
    'diproses'    => 'selesai',
    'dicuci'      => 'selesai',
    'dikeringkan' => 'selesai',
    'disetrika'   => 'selesai',
    'selesai'     => 'diambil',
    'diambil'     => null,
];

// Petugas yang mengubah status
$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$cekKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$cekKaryawan->execute(['id' => $idKaryawan]);
if (!$cekKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

try {
    $pdo->beginTransaction();

    // Lock semua pesanan yang dibuat dalam satu batch sebelum memvalidasi pengambilan.
    $stmtLockGroup = $pdo->prepare("
        SELECT id_pesanan
          FROM pesanan
         WHERE id_pelanggan = :id_pelanggan AND tanggal_pesan = :tanggal_pesan
         ORDER BY id_pesanan
         FOR UPDATE
    ");
    $stmtLockGroup->execute([
        'id_pelanggan' => $pesanan['id_pelanggan'],
        'tanggal_pesan' => $pesanan['tanggal_pesan'],
    ]);

    $stmt = $pdo->prepare("SELECT status FROM pesanan WHERE id_pesanan = :id");
    $stmt->execute(['id' => $idPesanan]);
    $statusSekarang = $stmt->fetchColumn();

    if ($statusSekarang === false) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
        header('Location: ' . $base . 'pesanan');
        exit;
    }

    $statusSekarang = strtolower($statusSekarang);
    $statusBerikut  = $nextMap[$statusSekarang] ?? null;

    if ($statusBerikut === null || $statusTujuan !== $statusBerikut) {
        $pdo->rollBack();
        kembaliDetail($idPesanan, 'flash_error', 'Perubahan status tidak valid atau pesanan sudah berubah. Silakan muat ulang halaman.');
    }

    if ($statusBerikut === 'diambil') {
        $stmtBelumLunas = $pdo->prepare("
            SELECT COUNT(*)
              FROM pesanan
             WHERE id_pelanggan = :id_pelanggan
               AND tanggal_pesan = :tanggal_pesan
               AND status_bayar <> 'lunas'
        ");
        $stmtBelumLunas->execute([
            'id_pelanggan' => $pesanan['id_pelanggan'],
            'tanggal_pesan' => $pesanan['tanggal_pesan'],
        ]);
        if ((int) $stmtBelumLunas->fetchColumn() > 0) {
            $pdo->rollBack();
            $_SESSION['flash_popup'] = 'Pesanan belum dibayar, tidak dapat diambil.';
            kembaliDetail($idPesanan, 'flash_error', 'Pesanan belum dibayar, tidak dapat diambil.');
        }
    }

    $upd = $pdo->prepare("UPDATE pesanan SET status = :status WHERE id_pesanan = :id");
    $upd->execute(['status' => $statusBerikut, 'id' => $idPesanan]);

    $riwayat = $pdo->prepare("
        INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
        VALUES (:id_pesanan, :id_karyawan, :status, NOW())
    ");
    $riwayat->execute([
        'id_pesanan'  => $idPesanan,
        'id_karyawan' => $idKaryawan,
        'status'      => $statusBerikut,
    ]);

    $pdo->commit();

    kembaliDetail($idPesanan, 'flash_sukses', 'Status pesanan berhasil diubah menjadi ' . ucfirst($statusBerikut) . '.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    kembaliDetail($idPesanan, 'flash_error', 'Gagal mengubah status: ' . $e->getMessage());
}
