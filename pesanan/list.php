<?php
$title = 'Data Layanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: $layanan = $pdo->query('SELECT * FROM layanan ORDER BY nama')->fetchAll();
$layanan = [];

require_once __DIR__ . '/../includes/header.php';
?>
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold"><?php echo e($title); ?></h1>
            <a href="tambah" class="text-white bg-sky-700 hover:bg-sky-800 rounded text-sm px-4 py-2">+ Tambah</a>
        </div>
        <!-- TODO: tabel foreach ($layanan as $row): nama, harga_per_kg, estimasi_hari,
             link edit?id=... dan form hapus (method post + csrf) -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
