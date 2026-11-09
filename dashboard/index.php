<?php
$title = 'Dashboard';
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';   // guard: belum login -> auth/login
require_once __DIR__ . '/../includes/header.php';
?>
        <h1 class="text-xl font-bold mb-4"><?php echo e($title); ?></h1>
        <p>Selamat datang, <?php echo e($_SESSION['nama'] ?? ''); ?>.</p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
