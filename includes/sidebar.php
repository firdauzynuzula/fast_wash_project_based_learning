<?php
/**
 * Fast Wash - Komponen Sidebar Navigasi Admin & Karyawan
 * Sesuai desain Figma (Header.png & Sidebar.png & Dokumen Pemilik)
 */
$base = $base ?? './';
$currentRoute = $route ?? '';
$userRole = $userRole ?? ($_SESSION['role'] ?? 'karyawan');
$isPemilik = ($userRole === 'pemilik' || $userRole === 'admin');
$userName = $isPemilik ? 'Akun Pemilik' : ($_SESSION['nama'] ?? 'Karyawan');
$userEmail = $isPemilik ? 'fastwash@gmail.com' : ($_SESSION['email'] ?? 'fastwash@gmail.com');
$userInitial = $isPemilik ? 'F' : strtoupper(substr(trim($userName), 0, 1) ?: 'K');

// Helper penentu menu aktif
$isMenu = function (array $prefixes) use ($currentRoute) {
    foreach ($prefixes as $p) {
        if ($p === '' && ($currentRoute === '' || $currentRoute === 'dashboard' || $currentRoute === 'dashboard/index')) {
            return true;
        }
        if ($p !== '' && str_starts_with($currentRoute, $p)) {
            return true;
        }
    }
    return false;
};
?>

<!-- Backdrop Mobile -->
<div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-xs transition-opacity hidden md:hidden" aria-hidden="true"></div>

<!-- Sidebar Container -->
<aside id="app-sidebar"
       class="fixed top-0 left-0 z-40 w-64 lg:w-72 h-screen transition-transform -translate-x-full md:translate-x-0 bg-white border-r border-slate-100 flex flex-col justify-between py-6 px-5 select-none shrink-0 overflow-y-auto"
       style="scrollbar-width: none; -ms-overflow-style: none;"
       aria-label="Sidebar Navigasi">

    <!-- Bagian Atas: Logo & Menu -->
    <div class="flex flex-col gap-6">

        <!-- Logo Fast Wash (Header.png) -->
        <div class="flex items-center justify-between px-2">
            <a href="<?php echo $base; ?>dashboard" class="flex items-center gap-3 group focus:outline-hidden">
                <span class="w-11 h-11 rounded-2xl bg-[#0085ff] flex items-center justify-center text-white shadow-md shadow-[#0085ff]/30 group-hover:scale-105 transition-transform">
                    <!-- Ikon Mesin Cuci -->
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4" y="2.5" width="16" height="19" rx="3"/>
                        <circle cx="12" cy="13.5" r="4.5"/>
                        <path d="M10.5 14.5a2 2 0 0 0 3-1.5"/>
                        <path d="M8 6h.01M11 6h.01"/>
                    </svg>
                </span>
                <span class="text-2xl font-extrabold tracking-tight text-[#0085ff]">Fast Wash</span>
            </a>

            <!-- Tombol Tutup di Mobile -->
            <button type="button" id="btn-close-sidebar" class="md:hidden text-slate-400 hover:text-slate-600 p-1.5 rounded-lg focus:outline-hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Daftar Menu Navigasi -->
        <nav class="flex flex-col gap-1.5 mt-2">

            <!-- 1. Dasbor -->
            <?php $act = $isMenu(['', 'dashboard']); ?>
            <a href="<?php echo $base; ?>dashboard"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="2"/>
                    <rect x="14" y="3" width="7" height="7" rx="2"/>
                    <rect x="14" y="14" width="7" height="7" rx="2"/>
                    <rect x="3" y="14" width="7" height="7" rx="2"/>
                </svg>
                <span>Dasbor</span>
            </a>

            <!-- 2. Pesanan -->
            <?php $act = $isMenu(['transaksi']); ?>
            <a href="<?php echo $base; ?>transaksi/status"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <!-- Ikon Baju / T-Shirt -->
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/>
                </svg>
                <span>Pesanan</span>
            </a>

            <!-- 3. Pelanggan -->
            <?php $act = $isMenu(['pelanggan']); ?>
            <a href="<?php echo $base; ?>pelanggan/list"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H3v-2a4 4 0 014-4h6a4 4 0 014 4v2m-3-10a4 4 0 11-8 0 4 4 0 018 0zm10 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Pelanggan</span>
            </a>

            <!-- 4. Pembayaran -->
            <?php $act = $isMenu(['pembayaran']); ?>
            <a href="<?php echo $base; ?>pembayaran"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                    <path d="M2.5 10h19"/>
                </svg>
                <span>Pembayaran</span>
            </a>

            <!-- 5. Laporan (Khusus Pemilik / Admin) -->
            <?php if ($isPemilik): ?>
            <?php $act = $isMenu(['laporan']); ?>
            <a href="<?php echo $base; ?>laporan"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 15 4-5 3 3 5-6"/>
                </svg>
                <span>Laporan</span>
            </a>
            <?php else: ?>
            <!-- 5. Absensi (Khusus Karyawan) -->
            <?php $act = $isMenu(['absensi']); ?>
            <a href="<?php echo $base; ?>absensi"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                    <path d="M8 2.5v4M16 2.5v4M3 10h18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 15.5 2 2 3.5-4"/>
                </svg>
                <span>Absensi</span>
            </a>
            <?php endif; ?>

            <!-- 6. Log Aktivitas -->
            <?php $act = $isMenu(['log-activity']); ?>
            <a href="<?php echo $base; ?>log-activity"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white font-medium rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 3.5v5h5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2"/>
                </svg>
                <span>Log Aktivitas</span>
            </a>

        </nav>
    </div>

    <!-- Bagian Bawah: Profil Pengguna & Keluar (Sidebar.png) -->
    <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">

        <!-- Kartu Profil Pengguna -->
        <div class="bg-[#eaf3fe] p-3 rounded-2xl flex items-center gap-3">
            <span class="w-10 h-10 rounded-full bg-[#cbe4fc] flex items-center justify-center text-[#0085ff] font-bold text-base shrink-0">
                <?php echo e($userInitial); ?>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate"><?php echo e($userName); ?></p>
                <p class="text-xs text-slate-500 truncate"><?php echo e($userEmail); ?></p>
            </div>
        </div>

        <!-- Tombol Keluar / Logout -->
        <a href="<?php echo $base; ?>auth/logout"
           class="flex items-center gap-3 px-3 py-2 text-sm font-semibold text-slate-500 hover:text-red-600 transition-colors rounded-xl hover:bg-red-50">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Keluar</span>
        </a>
    </div>

</aside>
