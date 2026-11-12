<?php
/**
 * Fast Wash - Tata Letak Header Bersama
 * Menampilkan tag <head>, kerangka utama satu layar penuh (h-screen overflow-hidden),
 * komponen sidebar, dan Top Header Bar (Top-bar.png).
 */
$base = $base ?? './';
$userRole = $_SESSION['role'] ?? 'karyawan';
$userName = $_SESSION['nama'] ?? ($userRole === 'pemilik' || $userRole === 'admin' ? 'Pemilik' : 'Karyawan');
$userRoleLabel = ($userRole === 'pemilik' || $userRole === 'admin') ? 'Pemilik' : 'Karyawan';
$userInitial = strtoupper(substr(trim($userName), 0, 1) ?: 'K');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash<?php echo isset($title) ? ' — ' . e($title) : ''; ?></title>
    <!-- Stylesheet Tailwind CSS v4 & Flowbite terkompilasi (dengan Cache Busting) -->
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/style.css'); ?>">
</head>
<body class="bg-[#EFF6FF] font-sans text-slate-800 antialiased h-screen overflow-hidden">

    <!-- Container Utama Layout Admin: Terkunci 1 layar peramban (No Scroll Global) -->
    <div class="h-screen flex overflow-hidden bg-[#EFF6FF]">

        <!-- Sidebar Komponen (Header.png & Sidebar.png) -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Wrapper Konten Utama (Offset kiri selebar sidebar pada desktop, flex vertikal h-screen) -->
        <div class="flex-1 flex flex-col min-w-0 md:ml-64 lg:ml-72 h-screen overflow-hidden bg-[#EFF6FF]">

            <!-- Top Header Bar (Top-bar.png) -->
            <header class="shrink-0 bg-[#FFFFFF] px-4 sm:px-6 md:px-8 py-3.5 flex items-center justify-between border-b border-slate-100 z-20">

                <!-- Kiri: Tombol Hamburger Mobile & Breadcrumb/Judul -->
                <div class="flex items-center gap-3">
                    <button type="button"
                            id="btn-open-sidebar"
                            class="md:hidden p-2 text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 focus:outline-hidden"
                            aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                        <nav class="flex items-center text-sm font-medium text-slate-500">
                            <?php foreach ($breadcrumb as $idx => $bc): ?>
                                <?php if ($idx > 0): ?>
                                    <svg class="w-4 h-4 mx-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                <?php endif; ?>
                                <?php if (!empty($bc['url'])): ?>
                                    <a href="<?php echo $base . $bc['url']; ?>" class="hover:text-[#0085ff] transition-colors"><?php echo e($bc['label']); ?></a>
                                <?php else: ?>
                                    <span class="text-slate-800 font-semibold"><?php echo e($bc['label']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </div>

                <!-- Kanan: Lonceng Notifikasi & Pill Status Profil (Top-bar.png) -->
                <div class="flex items-center gap-3">

                    <!-- Lonceng Notifikasi dengan Indikator Kuning -->
                    <button type="button"
                            class="relative p-2.5 bg-white rounded-2xl shadow-xs border border-slate-100 hover:bg-slate-50 text-slate-600 transition-colors focus:outline-hidden"
                            aria-label="Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <!-- Titik Indikator Kuning -->
                        <span class="absolute top-2 right-2 w-2 h-2 bg-amber-400 rounded-full ring-2 ring-white"></span>
                    </button>

                    <!-- Pill Badge Profil Pengguna (Top-bar.png) -->
                    <div class="flex items-center gap-2.5 pl-1.5 pr-4 py-1.5 bg-white rounded-full border border-blue-100 shadow-xs">
                        <div class="relative w-8 h-8 rounded-full bg-[#0085ff] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                            <?php echo e($userInitial); ?>
                            <!-- Titik Hijau Status Aktif -->
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white"></span>
                        </div>
                        <span class="text-sm font-semibold text-slate-700"><?php echo e($userRoleLabel); ?></span>
                    </div>

                </div>

            </header>

            <!-- Area Konten Utama: Scroll independen vertikal tanpa bilah gulir visual -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-6 md:px-8 py-5 w-full max-w-7xl mx-auto" style="scrollbar-width: none; -ms-overflow-style: none;">
