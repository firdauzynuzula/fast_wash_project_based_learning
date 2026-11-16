<?php
$title = 'Status Cucian';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: $transaksi = prepared SELECT transaksi JOIN pelanggan JOIN layanan
//        WHERE status != 'diambil' ORDER BY tanggal_masuk;
$transaksi = [];

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <!-- TODO: tabel foreach ($transaksi as $row): pelanggan, layanan, berat, total, status;
             tombol/form per baris ke proses_status untuk naik status
             (baru -> proses -> selesai -> diambil), method post + csrf + hidden id & status -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
