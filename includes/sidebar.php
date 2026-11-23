<?php
/**
 * Fast Wash - Komponen Sidebar Navigasi Admin & Karyawan
 * Sesuai desain Figma (Header.png & Sidebar.png & Dokumen Pemilik)
 */
$base = $base ?? './';
global $route;
$currentRoute = $route ?? '';

// Fallback deteksi rute aktif dari URL jika $route belum diset oleh controller/view
if ($currentRoute === '' && !empty($_SERVER['REQUEST_URI'])) {
    $cleanPath = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    foreach (['absensi', 'pesanan', 'pelanggan', 'pembayaran', 'laporan', 'log-activity', 'dashboard'] as $r) {
        if (preg_match('#(^|/)' . preg_quote($r, '#') . '(/|$)#i', $cleanPath)) {
            $currentRoute = $r;
            break;
        }
    }
}
$userRole = $_SESSION['role'] ?? ($userRole ?? 'karyawan');
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
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
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
            <?php $act = $isMenu(['pesanan']); ?>
            <a href="<?php echo $base; ?>pesanan"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <!-- Ikon Baju / T-Shirt (assets/img/svg/logo-pesanan.svg) -->
             <svg xmlns="http://www.w3.org/2000/svg" width="1.25rem" height="1.25rem" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<path fill="currentColor" d="m21.37 4.07l-5-2A.9.9 0 0 0 16 2h-1c-.55 0-1 .45-1 1c0 1.1-.9 2-2 2s-2-.9-2-2c0-.55-.45-1-1-1H8c-.13 0-.25.02-.37.07l-5 2A1 1 0 0 0 2 5v6c0 .55.45 1 1 1h2v9c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-9h2c.55 0 1-.45 1-1V5a1 1 0 0 0-.63-.93M20 10h-3v10H7V10H4V5.68l4.13-1.65c.45 1.71 2.02 2.98 3.87 2.98s3.41-1.26 3.87-2.98L20 5.68z" />
</svg>

                <span>Pesanan</span>
            </a>

            <!-- 3. Pelanggan -->
            <?php $act = $isMenu(['pelanggan']); ?>
            <a href="<?php echo $base; ?>pelanggan/list"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="1.25rem" height="1.25rem" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 20c0-1.742-1.67-3.223-4-3.773M15 20c0-2.21-2.686-4-6-4s-6 1.79-6 4m12-7a4 4 0 0 0 0-8m-6 8a4 4 0 1 1 0-8a4 4 0 0 1 0 8" />
            </svg>

                <span>Pelanggan</span>
            </a>

            <!-- 4. Pembayaran -->
            <?php $act = $isMenu(['pembayaran']); ?>
            <a href="<?php echo $base; ?>pembayaran"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
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
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="1.25rem" height="1.25rem" viewBox="0 0 24 24">
	<path d="M0 0h24v24H0z" fill="none" />
	<g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
		<path stroke-miterlimit="5.759" d="M3 3v16a2 2 0 0 0 2 2h16" />
		<path stroke-miterlimit="5.759" d="m7 14l4-4l4 4l6-6" />
		<path d="M18 8h3v3" />
	</g>
</svg>

                <span>Laporan</span>
            </a>
            <?php else: ?>
            <!-- 5. Absensi (Khusus Karyawan) -->
            <?php $act = $isMenu(['absensi']); ?>
            <a href="<?php echo $base; ?>absensi"
               class="<?php echo $act
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
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
                   ? 'bg-[#0085ff] text-white rounded-2xl shadow-sm shadow-[#0085ff]/20'
                   : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50 font-medium rounded-2xl'; ?> px-4 py-3 flex items-center gap-3.5 transition-colors"
               <?php if ($act) echo 'aria-current="page"'; ?>>
              <svg xmlns="http://www.w3.org/2000/svg" width="1.25rem" height="1.25rem" viewBox="0 0 1024 1024">
	<path d="M0 0h1024v1024H0z" fill="none" />
	<path fill="currentColor" d="M945 412H689c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h256c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8M811 548H689c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h122c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8M477.3 322.5H434c-6.2 0-11.2 5-11.2 11.2v248c0 3.6 1.7 6.9 4.6 9l148.9 108.6c5 3.6 12 2.6 15.6-2.4l25.7-35.1v-.1c3.6-5 2.5-12-2.5-15.6l-126.7-91.6V333.7c.1-6.2-5-11.2-11.1-11.2" />
	<path fill="currentColor" d="M804.8 673.9H747c-5.6 0-10.9 2.9-13.9 7.7c-12.7 20.1-27.5 38.7-44.5 55.7c-29.3 29.3-63.4 52.3-101.3 68.3c-39.3 16.6-81 25-124 25c-43.1 0-84.8-8.4-124-25c-37.9-16-72-39-101.3-68.3s-52.3-63.4-68.3-101.3c-16.6-39.2-25-80.9-25-124s8.4-84.7 25-124c16-37.9 39-72 68.3-101.3s63.4-52.3 101.3-68.3c39.2-16.6 81-25 124-25c43.1 0 84.8 8.4 124 25c37.9 16 72 39 101.3 68.3c17 17 31.8 35.6 44.5 55.7c3 4.8 8.3 7.7 13.9 7.7h57.8c6.9 0 11.3-7.2 8.2-13.3c-65.2-129.7-197.4-214-345-215.7c-216.1-2.7-395.6 174.2-396 390.1C71.6 727.5 246.9 903 463.2 903c149.5 0 283.9-84.6 349.8-215.8c3.1-6.1-1.4-13.3-8.2-13.3" />
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
