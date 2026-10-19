<?php
$title = 'Transaksi Baru';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: $pelanggan = $pdo->query('SELECT id, nama FROM pelanggan ORDER BY nama')->fetchAll();
// TODO: $layanan   = $pdo->query('SELECT id, nama, harga_per_kg FROM layanan ORDER BY nama')->fetchAll();
$pelanggan = [];
$layanan = [];

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <form action="proses_tambah" method="post" class="max-w-md space-y-3">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <!-- TODO: select pelanggan_id (dari $pelanggan), select layanan_id (dari $layanan,
                 tampilkan harga_per_kg), input berat_kg (number, step 0.1, min > 0) -->
            <button type="submit" class="text-white bg-sky-700 hover:bg-sky-800 rounded px-4 py-2">Simpan</button>
        </form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
