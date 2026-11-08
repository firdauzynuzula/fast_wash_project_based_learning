<?php
// Di-require dari view. $base dan session sudah siap dari bootstrap.php.
/** @var string $base Prefiks URL relatif ke root app (diset index.php / bootstrap.php). */
$base = $base ?? './';
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
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col justify-between">
    <header class="bg-[#1668dc] text-white shadow-sm">
        <div class="max-w-5xl mx-auto flex items-center justify-between px-4 py-3">
            <a href="<?php echo $base; ?>dashboard" class="text-lg font-bold flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-white text-[#1668dc] flex items-center justify-center font-extrabold text-sm">FW</span>
                Fast Wash
            </a>
            <nav>
                <ul class="flex gap-4 text-sm font-medium">
                    <li><a class="hover:underline" href="<?php echo $base; ?>dashboard">Beranda</a></li>
                    <?php if ($sudahLogin): ?>
                    <li><a class="hover:underline" href="<?php echo $base; ?>pelanggan/list">Pelanggan</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>layanan/list">Layanan</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/tambah">Pesanan Baru</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/status">Status Cucian</a></li>
                    <li><a class="hover:underline" href="<?php echo $base; ?>transaksi/riwayat">Riwayat</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="text-sm flex items-center gap-3">
                <?php if ($sudahLogin): ?>
                    <span class="font-medium bg-blue-800/40 px-2.5 py-1 rounded-full text-xs">
                        <?php echo e($_SESSION['nama'] ?? ''); ?> (<?php echo e(ucfirst($_SESSION['role'] ?? 'user')); ?>)
                    </span>
                    <a class="bg-white/10 hover:bg-white/20 px-3 py-1 rounded-lg text-xs font-semibold transition" href="<?php echo $base; ?>auth/logout">Keluar</a>
                <?php else: ?>
                    <a class="hover:underline" href="<?php echo $base; ?>auth/login">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">
