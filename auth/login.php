<?php
$title = 'Login';
require_once __DIR__ . '/../includes/bootstrap.php';

require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <form action="proses_login" method="post" class="max-w-md space-y-3">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <!-- TODO: input username + password; tampilkan pesan error jika ada -->
            <button type="submit" class="text-white bg-sky-700 hover:bg-sky-800 rounded px-4 py-2">Login</button>
        </form>
        <p class="mt-3 text-sm">Belum punya akun? <a class="text-sky-700 hover:underline" href="register">Register</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
