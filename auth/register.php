<?php
$title = 'Register';
require_once __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <form action="proses_register" method="post" class="max-w-md space-y-3">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <!-- TODO: input nama, username, password -->
            <button type="submit" class="text-white bg-sky-700 hover:bg-sky-800 rounded px-4 py-2">Daftar</button>
        </form>
        <p class="mt-3 text-sm">Sudah punya akun? <a class="text-sky-700 hover:underline" href="login">Login</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
