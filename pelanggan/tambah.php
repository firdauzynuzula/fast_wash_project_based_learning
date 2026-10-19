<?php
$title = 'Tambah Pelanggan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <form action="proses_tambah" method="post" class="max-w-md space-y-3">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <!-- TODO: input nama (wajib), telepon, alamat (textarea) -->
            <button type="submit" class="text-white bg-sky-700 hover:bg-sky-800 rounded px-4 py-2">Simpan</button>
        </form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
