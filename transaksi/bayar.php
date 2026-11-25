<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

$idPesanan  = (int) ($_POST['id_pesanan'] ?? 0);
$metode     = strtolower(trim($_POST['metode'] ?? ''));
$redirect   = trim($_POST['redirect'] ?? '');
$bayarBatch = ($_POST['bayar_batch'] ?? '') === '1';
$target     = $redirect !== '' ? $base . $redirect : $base . 'pesanan';

if (!in_array($metode, ['tunai', 'qris', 'transfer'], true)) {
    $_SESSION['flash_error'] = 'Silakan pilih metode pembayaran yang valid.';
    header('Location: ' . $target);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_pesanan, kode_pesanan, id_pelanggan, tanggal_pesan
      FROM pesanan
     WHERE id_pesanan = :id
");
$stmt->execute(['id' => $idPesanan]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    $_SESSION['flash_error'] = 'Pesanan tidak ditemukan.';
    header('Location: ' . $base . 'pesanan');
    exit;
}

$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$checkKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$checkKaryawan->execute(['id' => $idKaryawan]);
if (!$checkKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

try {
    $pdo->beginTransaction();

    $stmtLockGroup = $pdo->prepare("
        SELECT id_pesanan
          FROM pesanan
         WHERE id_pelanggan = :id_pelanggan
           AND tanggal_pesan = :tanggal_pesan
         ORDER BY id_pesanan
         FOR UPDATE
    ");
    $stmtLockGroup->execute([
        'id_pelanggan' => $pesanan['id_pelanggan'],
        'tanggal_pesan' => $pesanan['tanggal_pesan'],
    ]);

    $stmtGroup = $pdo->prepare("
        SELECT p.id_pesanan,
               p.kode_pesanan,
               p.total_harga,
               p.status,
               p.status_bayar,
               COALESCE((
                   SELECT SUM(t.jumlah_bayar)
                     FROM riwayat_transaksi t
                    WHERE t.id_pesanan = p.id_pesanan
                      AND t.status_transaksi = 'valid'
               ), 0) AS total_dibayar
          FROM pesanan p
         WHERE p.id_pelanggan = :id_pelanggan
           AND p.tanggal_pesan = :tanggal_pesan
         ORDER BY p.id_pesanan
    ");
    $stmtGroup->execute([
        'id_pelanggan' => $pesanan['id_pelanggan'],
        'tanggal_pesan' => $pesanan['tanggal_pesan'],
    ]);
    $grupPesanan = $stmtGroup->fetchAll();

    $pesananBelumLunas = array_values(array_filter(
        $grupPesanan,
        static fn(array $item): bool => $item['status_bayar'] !== 'lunas'
            && ($bayarBatch || (int) $item['id_pesanan'] === $idPesanan)
    ));

    if (!$pesananBelumLunas) {
        $pdo->commit();
        $_SESSION['flash_sukses'] = $bayarBatch
            ? 'Semua pesanan dalam transaksi ini sudah berstatus lunas.'
            : "Pesanan {$pesanan['kode_pesanan']} sudah berstatus Lunas.";
        header('Location: ' . $target);
        exit;
    }

    foreach ($grupPesanan as $item) {
        if ($item['status'] !== 'selesai') {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Pembayaran baru dapat dikonfirmasi setelah semua pesanan selesai.';
            header('Location: ' . $target);
            exit;
        }
    }

    $stmtBayar = $pdo->prepare("
        INSERT INTO riwayat_transaksi (
            id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi
        ) VALUES (
            :id_karyawan, :id_pesanan, NOW(), :jumlah_bayar, :jenis_pembayaran, 'valid'
        )
    ");

    $totalDibayar = 0.0;
    $jumlahPesananDibayar = 0;
    foreach ($pesananBelumLunas as $item) {
        $jumlahBayar = max(0, (float) $item['total_harga'] - (float) $item['total_dibayar']);
        if ($jumlahBayar <= 0) {
            continue;
        }

        $stmtBayar->execute([
            'id_karyawan'      => $idKaryawan,
            'id_pesanan'       => $item['id_pesanan'],
            'jumlah_bayar'     => $jumlahBayar,
            'jenis_pembayaran' => $metode,
        ]);
        $totalDibayar += $jumlahBayar;
        $jumlahPesananDibayar++;
    }

    $pdo->commit();

    if ($bayarBatch) {
        $_SESSION['flash_sukses'] = 'Pembayaran ' . $jumlahPesananDibayar
            . ' pesanan sebesar Rp ' . number_format($totalDibayar, 0, ',', '.')
            . ' (' . strtoupper($metode) . ') berhasil dikonfirmasi.';
    } else {
        $_SESSION['flash_sukses'] = "Pembayaran pesanan {$pesanan['kode_pesanan']} sebesar Rp "
            . number_format($totalDibayar, 0, ',', '.') . ' (' . strtoupper($metode) . ') berhasil dicatat.';
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Gagal mencatat pembayaran: ' . $e->getMessage();
}

header('Location: ' . $target);
exit;
