<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

csrf_check();

// Ambil data pelanggan (mode baru vs terdaftar)
$idPelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$namaBaru    = trim($_POST['nama_baru'] ?? '');
$teleponBaru = trim($_POST['telepon_baru'] ?? '');
$alamatBaru  = trim($_POST['alamat_baru'] ?? '');

if ($idPelanggan <= 0 && $namaBaru !== '') {
    if ($teleponBaru === '') {
        $_SESSION['flash_error'] = 'Nomor telepon pelanggan baru wajib diisi.';
        header('Location: ' . $base . 'transaksi/tambah');
        exit;
    }
    // Daftarkan pelanggan baru
    try {
        $stmtNew = $pdo->prepare("INSERT INTO pelanggan (nama, no_telepon, alamat, tanggal_daftar) VALUES (:nama, :telp, :alamat, NOW()) RETURNING id_pelanggan");
        $stmtNew->execute([
            'nama'   => $namaBaru,
            'telp'   => $teleponBaru,
            'alamat' => $alamatBaru ?: null,
        ]);
        $idPelanggan = (int) $stmtNew->fetchColumn();
    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Gagal menyimpan data pelanggan baru: ' . $e->getMessage();
        header('Location: ' . $base . 'transaksi/tambah');
        exit;
    }
}

if ($idPelanggan <= 0) {
    $_SESSION['flash_error'] = 'Silakan pilih pelanggan atau daftarkan pelanggan baru.';
    header('Location: ' . $base . 'transaksi/tambah');
    exit;
}

// Layanan & Berat
$idLayanan = (int) ($_POST['id_layanan'] ?? 0);
$qty       = (float) ($_POST['qty'] ?? 0);

if ($qty < 0.5) {
    $_SESSION['flash_error'] = 'Berat cucian minimal 0.5 kg.';
    header('Location: ' . $base . 'transaksi/tambah');
    exit;
}

// Ambil info layanan
$stmtLay = $pdo->prepare("SELECT id_layanan, nama_layanan, harga, durasi_jam FROM layanan WHERE id_layanan = :id");
$stmtLay->execute(['id' => $idLayanan]);
$layananRow = $stmtLay->fetch();

if (!$layananRow) {
    $_SESSION['flash_error'] = 'Layanan yang dipilih tidak valid.';
    header('Location: ' . $base . 'transaksi/tambah');
    exit;
}

$hargaSatuan   = (float) $layananRow['harga'];
$durasiJam     = (int) ($layananRow['durasi_jam'] ?: 24);
$isExpress     = !empty($_POST['layanan_cepat']);
$biayaTambahan = $isExpress ? 10000.00 : 0.00;
$catatanInput  = trim($_POST['catatan'] ?? '');

$subtotalLayanan = round($qty * $hargaSatuan, 2);
$totalHarga      = round($subtotalLayanan + $biayaTambahan, 2);

// Hitung estimasi selesai
$jamTambahan = $isExpress ? 6 : $durasiJam;
$estimasiSelesai = date('Y-m-d H:i:s', strtotime("+{$jamTambahan} hours"));

// Pembayaran
$statusBayarPilihan = ($_POST['status_bayar_pilihan'] ?? 'belum_lunas') === 'lunas' ? 'lunas' : 'belum_lunas';
$metodePembayaran   = strtolower(trim($_POST['metode_pembayaran'] ?? 'tunai'));
if (!in_array($metodePembayaran, ['tunai', 'qris', 'transfer'], true)) {
    $metodePembayaran = 'tunai';
}

// Petugas/Karyawan ID
$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
// Pastikan karyawan valid di database
$checkKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$checkKaryawan->execute(['id' => $idKaryawan]);
if (!$checkKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

// Catatan gabungan
$catatanFinal = $catatanInput;
if ($isExpress) {
    $catatanFinal = ($catatanFinal !== '' ? ($catatanFinal . ' | ') : '') . 'Layanan Cepat (6 Jam)';
}

try {
    $pdo->beginTransaction();

    // Buat kode pesanan unik (contoh: #FW24102 atau FW-102)
    $stmtMax = $pdo->query("SELECT MAX(id_pesanan) FROM pesanan");
    $nextNum = ((int) $stmtMax->fetchColumn()) + 1;
    $kodePesanan = '#FW' . date('y') . sprintf('%03d', $nextNum);

    // Cek tabrakan kode unik
    $stmtCek = $pdo->prepare("SELECT id_pesanan FROM pesanan WHERE kode_pesanan = :k");
    $stmtCek->execute(['k' => $kodePesanan]);
    if ($stmtCek->fetch()) {
        $kodePesanan .= '-' . rand(10, 99);
    }

    // Insert pesanan
    $stmtPesanan = $pdo->prepare("
        INSERT INTO pesanan (
            id_pelanggan, id_karyawan, kode_pesanan, tanggal_pesan,
            estimasi_selesai, status, subtotal_layanan, biaya_tambahan,
            total_harga, status_bayar, catatan
        ) VALUES (
            :id_pelanggan, :id_karyawan, :kode_pesanan, NOW(),
            :estimasi_selesai, 'diterima', :subtotal_layanan, :biaya_tambahan,
            :total_harga, :status_bayar, :catatan
        ) RETURNING id_pesanan
    ");

    $stmtPesanan->execute([
        'id_pelanggan'     => $idPelanggan,
        'id_karyawan'      => $idKaryawan,
        'kode_pesanan'     => $kodePesanan,
        'estimasi_selesai' => $estimasiSelesai,
        'subtotal_layanan' => $subtotalLayanan,
        'biaya_tambahan'   => $biayaTambahan,
        'total_harga'      => $totalHarga,
        'status_bayar'     => $statusBayarPilihan,
        'catatan'          => $catatanFinal ?: null,
    ]);

    $idPesananBaru = (int) $stmtPesanan->fetchColumn();

    // Insert detail_pesanan
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
        VALUES (:id_pesanan, :id_layanan, :qty, :harga_satuan, :catatan)
    ");
    $stmtDetail->execute([
        'id_pesanan'   => $idPesananBaru,
        'id_layanan'   => $idLayanan,
        'qty'          => $qty,
        'harga_satuan' => $hargaSatuan,
        'catatan'      => $catatanInput ?: null,
    ]);

    // Insert riwayat_status_pesanan ('diterima')
    $stmtRiwayat = $pdo->prepare("
        INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
        VALUES (:id_pesanan, :id_karyawan, 'diterima', NOW())
    ");
    $stmtRiwayat->execute([
        'id_pesanan'  => $idPesananBaru,
        'id_karyawan' => $idKaryawan,
    ]);

    // Jika bayar sekarang, insert ke riwayat_transaksi
    if ($statusBayarPilihan === 'lunas') {
        $stmtTrx = $pdo->prepare("
            INSERT INTO riwayat_transaksi (
                id_karyawan, id_pesanan, tanggal_bayar, jumlah_bayar, jenis_pembayaran, status_transaksi
            ) VALUES (
                :id_karyawan, :id_pesanan, NOW(), :jumlah_bayar, :jenis_pembayaran, 'valid'
            )
        ");
        $stmtTrx->execute([
            'id_karyawan'      => $idKaryawan,
            'id_pesanan'       => $idPesananBaru,
            'jumlah_bayar'     => $totalHarga,
            'jenis_pembayaran' => $metodePembayaran,
        ]);
    }

    $pdo->commit();

    $_SESSION['flash_sukses'] = "Pesanan {$kodePesanan} berhasil dibuat!";
    header('Location: ' . $base . 'transaksi/sukses?id=' . $idPesananBaru);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat membuat pesanan: ' . $e->getMessage();
    header('Location: ' . $base . 'transaksi/tambah');
    exit;
}
