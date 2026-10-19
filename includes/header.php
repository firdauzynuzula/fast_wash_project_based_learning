<?php
// Di-require dari view (app/Views/...). $base dan session sudah siap dari bootstrap.php.
$sudahLogin = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash<?php echo isset($title) ? ' | ' . e($title) : ''; ?></title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body class="bg-gray-50 text-gray-900">
    <header class="bg-sky-700 text-white">
        <div class="max-w-5xl mx-auto flex items-center justify-between px-4 py-3">
            <a href="<?php echo $base; ?>index" class="text-lg font-bold">Fast Wash</a>
            <nav>
                <ul class="flex gap-4 text-sm">
                    <li><a class="hover:underline" href="<?php echo $base; ?>index">Beranda</a></li>
                    <?php if ($sudahLogin): ?>
                    <li><a class="hover:underline" href="<?php echo $base; ?>pelanggan/list">Pelanggan</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>layanan/list">Layanan</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/tambah">Transaksi Baru</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/status">Status Cucian</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/riwayat">Riwayat</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-sm">
                <?php if ($sudahLogin): ?>
                    <span class="mr-2"><?php echo e($_SESSION['nama'] ?? ''); ?></span>
                    <a class="hover:underline" href="<?php echo $base; ?>auth/logout">Logout</a>
                <?php else: ?>
                    <a class="hover:underline" href="<?php echo $base; ?>auth/login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 py-6">
