<?php
/**
 * Fast Wash - Halaman Dasbor Karyawan & Pemilik
 * Sesuai desain Figma (Dashboard-Karyawan.png)
 */
$title = 'Dasbor';
$tanpaDb = true;

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Data Contoh Mockup Sesuai Kolom Database & View v_dasbor_hari_ini
$userName = $_SESSION['nama'] ?? 'Rani';
$hariIniIndonesia = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
][date('l')] . ', ' . date('d F Y');

// 4 Kartu Metrik KPI (v_dasbor_hari_ini)
$metrik = [
    'pesanan_hari_ini'   => 14,
    'selisih_kemarin'    => '+3 dari kemarin',
    'sedang_diproses'    => 6,
    'info_proses'        => '2 layanan cepat',
    'siap_diambil'       => 4,
    'info_siap'          => '1 menunggu bayar',
    'jam_kerja'          => '5j 42m',
    'jam_masuk'          => 'Check-in 07:58',
];

// Antrian Pengerjaan Terbaru (v_pesanan_terbaru)
$antrianPengerjaan = [
    [
        'id_pesanan'     => 1,
        'kode_pesanan'   => '#FW24042',
        'nama_pelanggan' => 'Nadya Alya',
        'layanan'        => 'Cuci + Setrika',
        'berat_kg'       => 3,
        'status'         => 'dicuci',
        'badge_label'    => 'Dicuci',
        'badge_class'    => 'bg-sky-50 text-sky-600 border border-sky-100',
    ],
    [
        'id_pesanan'     => 2,
        'kode_pesanan'   => '#FW24041',
        'nama_pelanggan' => 'Sarah Amanda',
        'layanan'        => 'Cuci Kering',
        'berat_kg'       => 2,
        'status'         => 'selesai',
        'badge_label'    => 'Siap Diambil',
        'badge_class'    => 'bg-emerald-50 text-emerald-600 border border-emerald-100',
    ],
    [
        'id_pesanan'     => 3,
        'kode_pesanan'   => '#FW24039',
        'nama_pelanggan' => 'Rizky Pratama',
        'layanan'        => 'Cuci + Setrika',
        'berat_kg'       => 4,
        'status'         => 'disetrika',
        'badge_label'    => 'Disetrika',
        'badge_class'    => 'bg-amber-50 text-amber-600 border border-amber-100',
    ],
    [
        'id_pesanan'     => 4,
        'kode_pesanan'   => '#FW24038',
        'nama_pelanggan' => 'Dimas Aditya',
        'layanan'        => 'Cuci Kering',
        'berat_kg'       => 6,
        'status'         => 'diterima',
        'badge_label'    => 'Diterima',
        'badge_class'    => 'bg-slate-100 text-slate-600 border border-slate-200',
    ],
];

// Data Presensi Hari Ini (absensi)
$presensiHariIni = [
    'jam_masuk'  => '07:58',
    'jam_pulang' => '--:--',
    'status'     => 'hadir',
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="h-full flex flex-col justify-between gap-3 sm:gap-4 lg:gap-5 min-h-0 bg-[#EFF6FF]">

    <!-- 1. Sapaan & Tombol Pesanan Baru (Header Section - Shrink 0) -->
    <div class="shrink-0 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <p class="text-xs sm:text-sm font-semibold text-[#0085ff]">Selamat datang, <?php echo e($userName); ?></p>
            <h1 class="text-xl sm:text-2xl font-extrabold text-[#11315b] tracking-tight">Berikut tugas Anda hari ini</h1>
        </div>
        <a href="<?php echo $base; ?>transaksi/tambah"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#0085ff] hover:bg-[#0073e6] text-white font-bold rounded-2xl shadow-md shadow-[#0085ff]/30 hover:shadow-lg transition-all shrink-0 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            <span>Pesanan Baru</span>
        </a>
    </div>

    <!-- 2. Empat Kartu Ringkasan Metrik (KPI Cards - Shrink 0) -->
    <div class="shrink-0 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

        <!-- Kartu 1: Pesanan Hari Ini -->
        <div class="bg-white rounded-2xl lg:rounded-3xl p-3.5 sm:p-4 shadow-xs border border-slate-100 flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Pesanan Hari Ini</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-800 mt-0.5"><?php echo $metrik['pesanan_hari_ini']; ?></p>
                <p class="text-[11px] font-semibold text-emerald-500 mt-0.5"><?php echo e($metrik['selisih_kemarin']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/>
                </svg>
            </div>
        </div>

        <!-- Kartu 2: Sedang Diproses -->
        <div class="bg-white rounded-2xl lg:rounded-3xl p-3.5 sm:p-4 shadow-xs border border-slate-100 flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Sedang Diproses</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-800 mt-0.5"><?php echo $metrik['sedang_diproses']; ?></p>
                <p class="text-[11px] font-semibold text-teal-600 mt-0.5"><?php echo e($metrik['info_proses']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"/>
                </svg>
            </div>
        </div>

        <!-- Kartu 3: Siap Diambil -->
        <div class="bg-white rounded-2xl lg:rounded-3xl p-3.5 sm:p-4 shadow-xs border border-slate-100 flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Siap Diambil</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-800 mt-0.5"><?php echo $metrik['siap_diambil']; ?></p>
                <p class="text-[11px] font-semibold text-emerald-600 mt-0.5"><?php echo e($metrik['info_siap']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>

        <!-- Kartu 4: Jam Kerja -->
        <div class="bg-white rounded-2xl lg:rounded-3xl p-3.5 sm:p-4 shadow-xs border border-slate-100 flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Jam Kerja</p>
                <p class="text-2xl sm:text-3xl font-extrabold text-slate-800 mt-0.5"><?php echo $metrik['jam_kerja']; ?></p>
                <p class="text-[11px] font-medium text-slate-400 mt-0.5"><?php echo e($metrik['jam_masuk']); ?></p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
        </div>

    </div>

    <!-- 3. Dua Kolom Utama: Antrian Pengerjaan & Absensi Hari Ini (Flex 1 Min-H-0) -->
    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">

        <!-- Kolom Kiri (2/3): Antrian Pengerjaan -->
        <div class="lg:col-span-2 bg-white rounded-2xl lg:rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100 flex flex-col justify-between min-h-0">
            <div class="shrink-0 flex items-center justify-between pb-2.5 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-800">Antrian Pengerjaan</h2>
                <a href="<?php echo $base; ?>transaksi/status" class="text-xs sm:text-sm font-semibold text-[#0085ff] hover:underline">Lihat Semua</a>
            </div>

            <div class="flex-1 flex flex-col justify-around divide-y divide-slate-100 my-1 overflow-hidden">
                <?php foreach ($antrianPengerjaan as $item): ?>
                    <div class="py-1.5 flex items-center justify-between gap-3 hover:bg-slate-50/50 rounded-xl px-2 -mx-2 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/>
                                </svg>
                            </div>
                            <div class="truncate">
                                <p class="text-sm font-bold text-slate-800 truncate"><?php echo e($item['nama_pelanggan']); ?></p>
                                <p class="text-xs text-slate-400 truncate">
                                    <span class="font-medium text-slate-600"><?php echo e($item['kode_pesanan']); ?></span> &middot; <?php echo e($item['layanan']); ?> &middot; <?php echo $item['berat_kg']; ?> kg
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold shrink-0 <?php echo $item['badge_class']; ?>">
                            <?php echo e($item['badge_label']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kolom Kanan (1/3): Absensi Hari Ini -->
        <div class="bg-white rounded-2xl lg:rounded-3xl p-4 sm:p-5 shadow-xs border border-slate-100 flex flex-col justify-between min-h-0">
            <div class="shrink-0">
                <h2 class="text-base font-bold text-slate-800">Absensi Hari Ini</h2>
                <p class="text-xs text-slate-400 mt-0.5"><?php echo e($hariIniIndonesia); ?></p>

                <!-- Box Jam Masuk & Pulang -->
                <div class="grid grid-cols-2 gap-2.5 mt-3 sm:mt-4">
                    <div class="bg-[#e6f9ed] rounded-2xl p-3">
                        <p class="text-[11px] font-semibold text-slate-500">Check-in</p>
                        <p class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-0.5"><?php echo e($presensiHariIni['jam_masuk']); ?></p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-3">
                        <p class="text-[11px] font-semibold text-slate-500">Check-out</p>
                        <p class="text-xl sm:text-2xl font-bold text-slate-400 mt-0.5"><?php echo e($presensiHariIni['jam_pulang']); ?></p>
                    </div>
                </div>
            </div>

            <a href="<?php echo $base; ?>absensi"
               class="shrink-0 w-full mt-3 py-2.5 sm:py-3 border-2 border-[#0085ff] text-[#0085ff] hover:bg-blue-50/70 font-bold rounded-2xl flex items-center justify-center gap-2 transition-colors text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <span>Buka Absensi</span>
            </a>
        </div>

    </div>

</div>

            <!-- Penutup Tag Kontainer Layout (Tanpa footer.php) -->
            </main>
        </div>
    </div>

    <!-- Toast Notifikasi Global (Form Mockup / Demo Mode) -->
    <div id="toast-demo" class="fixed bottom-5 right-5 z-50 hidden max-w-sm p-4 text-slate-700 bg-white rounded-2xl shadow-xl border border-slate-100 flex items-start gap-3 transition-all" role="alert">
        <span class="p-2 bg-blue-50 text-[#0085ff] rounded-xl shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <div class="text-sm">
            <p class="font-bold text-slate-900" id="toast-demo-title">Mode Pratinjau Frontend</p>
            <p class="text-xs text-slate-500 mt-0.5" id="toast-demo-message">Aksi ini disimulasikan menggunakan data contoh dan belum terhubung ke database.</p>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600 ml-auto p-1" onclick="document.getElementById('toast-demo').classList.add('hidden')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Dependensi Skrip -->
    <script src="<?php echo asset_url('assets/vendor/flowbite.min.js'); ?>"></script>
    <script src="<?php echo asset_url('assets/js/app.js'); ?>"></script>
</body>
</html>
