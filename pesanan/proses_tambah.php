<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'pesanan');
    exit;
}

csrf_check();

function kembaliError(string $pesan): void
{
    global $base;
    $_SESSION['flash_error'] = $pesan;
    header('Location: ' . $base . 'pesanan/tambah');
    exit;
}

/* ---------- Pelanggan ---------- */
$idPelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$namaBaru    = trim($_POST['nama_baru'] ?? '');
$teleponBaru = trim($_POST['telepon_baru'] ?? '');

if ($idPelanggan <= 0) {
    if ($namaBaru === '') {
        kembaliError('Silakan pilih pelanggan atau isi nama pelanggan baru.');
    }
    if ($teleponBaru === '') {
        kembaliError('Nomor WhatsApp pelanggan baru wajib diisi.');
    }
}

/* ---------- Daftar pesanan (form bisa lebih dari 1) ---------- */
$orders = $_POST['orders'] ?? [];
if (!is_array($orders) || count($orders) === 0) {
    kembaliError('Data pesanan tidak ditemukan.');
}

$statusBayar = (($_POST['status_bayar'] ?? '') === 'lunas') ? 'lunas' : 'belum_lunas';

$stmtLay = $pdo->prepare("SELECT id_layanan, nama_layanan, harga, durasi_jam FROM layanan WHERE id_layanan = :id");
$items = [];

foreach ($orders as $o) {
    $idLayanan = (int) ($o['id_layanan'] ?? 0);
    $qty       = (float) ($o['qty'] ?? 0);

    if ($qty < 0.5) {
        kembaliError('Berat cucian minimal 0.5 kg.');
    }

    $stmtLay->execute(['id' => $idLayanan]);
    $lay = $stmtLay->fetch();
    if (!$lay) {
        kembaliError('Layanan yang dipilih tidak valid.');
    }

    $metode = strtolower(trim($o['metode_pembayaran'] ?? 'tunai'));
    if (!in_array($metode, ['tunai', 'qris', 'transfer'], true)) {
        $metode = 'tunai';
    }

    $isExpress = !empty($o['layanan_cepat']);
    $harga     = (float) $lay['harga'];
    $durasiJam = (int) ($lay['durasi_jam'] ?: 24);
    $biaya     = $isExpress ? 10000.00 : 0.00;
    $catatan   = trim($o['catatan'] ?? '');

    $items[] = [
        'id_layanan' => $idLayanan,
        'qty'        => $qty,
        'harga'      => $harga,
        'subtotal'   => round($qty * $harga, 2),
        'biaya'      => $biaya,
        'total'      => round($qty * $harga + $biaya, 2),
        'jam'        => $isExpress ? 6 : $durasiJam,
        'express'    => $isExpress,
        'metode'     => $metode,
        'catatan'    => $catatan,
    ];
}

/* ---------- Karyawan / petugas ---------- */
$idKaryawan = (int) ($_SESSION['id_karyawan'] ?? $_SESSION['user_id'] ?? 1);
$checkKaryawan = $pdo->prepare("SELECT id_karyawan FROM karyawan WHERE id_karyawan = :id");
$checkKaryawan->execute(['id' => $idKaryawan]);
if (!$checkKaryawan->fetch()) {
    $idKaryawan = (int) $pdo->query("SELECT id_karyawan FROM karyawan LIMIT 1")->fetchColumn() ?: 1;
}

/* ---------- Simpan (satu transaksi untuk semua pesanan) ---------- */
try {
    $pdo->beginTransaction();

    if ($idPelanggan <= 0) {
        $stmtNew = $pdo->prepare("INSERT INTO pelanggan (nama, no_telepon, tanggal_daftar) VALUES (:nama, :telp, NOW()) RETURNING id_pelanggan");
        $stmtNew->execute(['nama' => $namaBaru, 'telp' => $teleponBaru]);
        $idPelanggan = (int) $stmtNew->fetchColumn();
    }

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
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_pesanan (id_pesanan, id_layanan, qty, harga_satuan, catatan)
        VALUES (:id_pesanan, :id_layanan, :qty, :harga_satuan, :catatan)
    ");
    $stmtRiwayat = $pdo->prepare("
        INSERT INTO riwayat_status_pesanan (id_pesanan, id_karyawan, status, waktu)
        VALUES (:id_pesanan, :id_karyawan, 'diterima', NOW())
    ");
    $stmtCekKode = $pdo->prepare("SELECT 1 FROM pesanan WHERE kode_pesanan = :k");

    // PERBAIKAN: tambah id_karyawan dan jumlah_bayar (NOT NULL)
    // Jika nama kolom di tabelmu berbeda, samakan dengan transaksi/bayar.php
    $stmtTrx = $pdo->prepare("
        INSERT INTO riwayat_transaksi (id_pesanan, id_karyawan, jumlah_bayar, jenis_pembayaran, status_transaksi, tanggal_bayar)
        VALUES (:id_pesanan, :id_karyawan, :jumlah_bayar, :jenis, 'valid', NOW())
    ");

    $idsBaru = [];

    foreach ($items as $it) {
        $nextNum = ((int) $pdo->query("SELECT MAX(id_pesanan) FROM pesanan")->fetchColumn()) + 1;
        $kode = '#FW' . date('y') . sprintf('%03d', $nextNum);

        $stmtCekKode->execute(['k' => $kode]);
        if ($stmtCekKode->fetch()) {
            $kode .= '-' . rand(10, 99);
        }

        $catatanFinal = $it['catatan'];
        if ($it['express']) {
            $catatanFinal = ($catatanFinal !== '' ? $catatanFinal . ' | ' : '') . 'Layanan Cepat (6 Jam)';
        }

        $stmtPesanan->execute([
            'id_pelanggan'     => $idPelanggan,
            'id_karyawan'      => $idKaryawan,
            'kode_pesanan'     => $kode,
            'estimasi_selesai' => date('Y-m-d H:i:s', strtotime("+{$it['jam']} hours")),
            'subtotal_layanan' => $it['subtotal'],
            'biaya_tambahan'   => $it['biaya'],
            'total_harga'      => $it['total'],
            'status_bayar'     => $statusBayar,
            'catatan'          => $catatanFinal ?: null,
        ]);
        $idPesanan = (int) $stmtPesanan->fetchColumn();

        $stmtDetail->execute([
            'id_pesanan'   => $idPesanan,
            'id_layanan'   => $it['id_layanan'],
            'qty'          => $it['qty'],
            'harga_satuan' => $it['harga'],
            'catatan'      => $it['catatan'] ?: null,
        ]);

        $stmtRiwayat->execute([
            'id_pesanan'  => $idPesanan,
            'id_karyawan' => $idKaryawan,
        ]);

        if ($statusBayar === 'lunas') {
            $stmtTrx->execute([
                'id_pesanan'   => $idPesanan,
                'id_karyawan'  => $idKaryawan,
                'jumlah_bayar' => $it['total'],
                'jenis'        => $it['metode'],
            ]);
        }

        $idsBaru[] = $idPesanan;
    }

    $pdo->commit();

    header('Location: ' . $base . 'pesanan/sukses?ids=' . implode(',', $idsBaru));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    kembaliError('Terjadi kesalahan sistem saat membuat pesanan: ' . $e->getMessage());
}
