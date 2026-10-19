<?php
$title = 'Data Pelanggan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: $pelanggan = $pdo->query('SELECT * FROM pelanggan ORDER BY nama')->fetchAll();
$pelanggan = [];

require_once __DIR__ . '/../includes/header.php';
?>
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold"><?php echo e($title); ?></h1>
            <a href="tambah" class="text-white bg-sky-700 hover:bg-sky-800 rounded text-sm px-4 py-2">+ Tambah</a>
        </div>
        <!-- TODO: tabel foreach ($pelanggan as $row): nama, telepon, alamat,
             link edit?id=... dan form hapus (method post + csrf) -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
