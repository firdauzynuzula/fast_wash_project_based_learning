<?php
$title = 'Riwayat Transaksi';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: $riwayat = prepared SELECT transaksi JOIN pelanggan JOIN layanan ORDER BY tanggal_masuk DESC
//        (opsional: filter $_GET['pelanggan_id'] atau $_GET['search'] dari form Beranda)
$riwayat = [];

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <!-- TODO: tabel foreach ($riwayat as $row): tanggal_masuk, pelanggan, layanan,
             berat, total_harga, status, tanggal_selesai, tanggal_diambil -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
