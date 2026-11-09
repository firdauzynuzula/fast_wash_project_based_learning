<?php
/**
 * Fast Wash - Halaman Log Aktivitas Sistem (Audit Trail)
 * Sesuai desain Figma Container.png & spesifikasi openspec/changes/implement-frontend-views/specs/log-aktivitas/
 * Fitur:
 * - Header kartu putih elegan dengan badge "Audit Trail Keamanan & Operasional", status real-time sync, dan info ledger tamper-proof
 * - Toolbar filter pencarian, filter peran (Pemilik/Karyawan), filter kategori, dan tombol "Ekspor Log"
 * - Header tabel warna biru solid (#0085ff) dengan kolom: Tanggal, Pengguna & Peran, Kategori, Deskripsi Aktivitas, dan IP Adress
 * - Tombol "Rincian" interaktif dan sifat immutable (tanpa fitur edit / hapus)
 * - Paginasi maks 3 entri per halaman dengan teks ringkasan "Menampilkan X dari Y aktivitas hari ini"
 */
$title = 'Log Aktivitas';
$route = $route ?? 'log-activity';
$tanpaDb = true;

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Breadcrumb opsional untuk Top Header Bar
$breadcrumb = [
    ['label' => 'Dasbor', 'url' => 'dashboard'],
    ['label' => 'Log Aktivitas', 'url' => '']
];

// Data Mockup Sesuai Desain Figma (assets/img/Container.png) & Skema Database
$mockLogs = [
    [
        'id_log'        => 101,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '08.50 WIB',
        'waktu'         => '2026-09-27 08:50:00',
        'nama_pengguna' => 'Dimas Prayoga',
        'inisial'       => 'DP',
        'role'          => 'karyawan',
        'kategori'      => 'Status Pesanan',
        'deskripsi'     => 'Membuat pesanan baru #FW24043 untuk pelanggan Dewi Sartika',
        'kode_pesanan'  => '#FW24043',
        'tags'          => [
            'Cuci + Setrika 4.0 kg',
            'Total: Rp 32.000'
        ],
        'ip_address'    => '192.168.1.12',
        'has_rincian'   => false,
    ],
    [
        'id_log'        => 102,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '08.50 WIB',
        'waktu'         => '2026-09-27 08:50:00',
        'nama_pengguna' => 'Reva Rosalina',
        'inisial'       => 'RR',
        'role'          => 'karyawan',
        'kategori'      => 'Operasional',
        'deskripsi'     => 'Memperbarui progres cucian #FW24042 dari Dicuci menjadi Dikeringkan',
        'kode_pesanan'  => '#FW24042',
        'status_lama'   => 'Dicuci',
        'status_baru'   => 'Dikeringkan',
        'tags'          => [
            'Dryer Bay #02',
            'Durasi 45 Menit'
        ],
        'ip_address'    => '192.168.1.15',
        'has_rincian'   => true,
    ],
    [
        'id_log'        => 103,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '08.50 WIB',
        'waktu'         => '2026-09-27 08:50:00',
        'nama_pengguna' => 'Fast Wash',
        'inisial'       => 'FW',
        'role'          => 'pemilik',
        'kategori'      => 'Transaksi',
        'deskripsi'     => 'Mengonfirmasi pencairan kas harian & verifikasi rekonsiliasi QRIS Rp 1.250.000',
        'kode_pesanan'  => null,
        'tags'          => [
            'BCA Settlement: Sukses',
            'Batch #QR-9942'
        ],
        'tag_highlight' => 'BCA Settlement: Sukses',
        'ip_address'    => '192.168.1.2',
        'has_rincian'   => true,
    ],
    [
        'id_log'        => 104,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '08.15 WIB',
        'waktu'         => '2026-09-27 08:15:00',
        'nama_pengguna' => 'Rani Melani',
        'inisial'       => 'RM',
        'role'          => 'karyawan',
        'kategori'      => 'Operasional',
        'deskripsi'     => 'Presensi check-in mandiri kehadiran staf operasional outlet',
        'kode_pesanan'  => null,
        'tags'          => [
            'Presensi: Tepat Waktu',
            'Shift Pagi'
        ],
        'ip_address'    => '192.168.1.14',
        'has_rincian'   => true,
    ],
    [
        'id_log'        => 105,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '08.00 WIB',
        'waktu'         => '2026-09-27 08:00:00',
        'nama_pengguna' => 'Budi Santoso',
        'inisial'       => 'BS',
        'role'          => 'pemilik',
        'kategori'      => 'Keamanan',
        'deskripsi'     => 'Login berhasil ke panel manajemen pemilik Fast Wash',
        'kode_pesanan'  => null,
        'tags'          => [
            '2FA: Terverifikasi',
            'Perangkat: macOS Chrome'
        ],
        'ip_address'    => '192.168.1.2',
        'has_rincian'   => true,
    ],
    [
        'id_log'        => 106,
        'tanggal'       => '27 Sep 2026',
        'jam'           => '07.45 WIB',
        'waktu'         => '2026-09-27 07:45:00',
        'nama_pengguna' => 'Siti Nurhaliza',
        'inisial'       => 'SN',
        'role'          => 'karyawan',
        'kategori'      => 'Transaksi',
        'deskripsi'     => 'Menerima pembayaran tunai pesanan #FW24041 sebesar Rp 25.000',
        'kode_pesanan'  => '#FW24041',
        'tags'          => [
            'Metode: Tunai',
            'Status: Lunas'
        ],
        'ip_address'    => '192.168.1.18',
        'has_rincian'   => false,
    ],
];

// Parameter Filter & Paginasi (Maksimal 3 log per halaman sesuai spesifikasi)
$filterRole     = trim($_GET['role'] ?? '');
$filterKategori = trim($_GET['kategori'] ?? '');
$keyword        = trim($_GET['q'] ?? '');
$perPage        = 3;

// Logika Pemfilteran Data
$filteredLogs = array_values(array_filter($mockLogs, function ($log) use ($filterRole, $filterKategori, $keyword) {
    if ($filterRole !== '' && strtolower($log['role']) !== strtolower($filterRole)) {
        return false;
    }
    if ($filterKategori !== '' && strtolower($log['kategori']) !== strtolower($filterKategori)) {
        return false;
    }
    if ($keyword !== '') {
        $kw = mb_strtolower($keyword);
        $inDeskripsi = mb_stripos($log['deskripsi'], $kw) !== false;
        $inKode      = !empty($log['kode_pesanan']) && mb_stripos($log['kode_pesanan'], $kw) !== false;
        $inIp        = mb_stripos($log['ip_address'], $kw) !== false;
        $inUser      = mb_stripos($log['nama_pengguna'], $kw) !== false;
        $inKategori  = mb_stripos($log['kategori'], $kw) !== false;
        if (!$inDeskripsi && !$inKode && !$inIp && !$inUser && !$inKategori) {
            return false;
        }
    }
    return true;
}));

// Kalkulasi Paginasi
$totalLogs   = count($filteredLogs);
$totalPages  = max(1, (int)ceil($totalLogs / $perPage));
$page        = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$offset      = ($page - 1) * $perPage;
$pagedLogs   = array_slice($filteredLogs, $offset, $perPage);
$countOnPage = count($pagedLogs);

// Muat Header Bersama (membuka <body>, sidebar, dan tag <main>)
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">

    <!-- 1. Header Card (Sesuai Visual Figma: assets/img/Container.png) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        
        <!-- Sisi Kiri: Badge Keamanan, Judul, dan Deskripsi -->
        <div class="max-w-2xl">
            <!-- Badge Audit Trail Keamanan & Operasional -->
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50/80 border border-blue-100 text-[#0085ff] text-xs font-semibold mb-2.5">
                <svg class="w-3.5 h-3.5 text-[#0085ff]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Audit Trail Keamanan & Operasional</span>
            </div>

            <!-- Judul Besar -->
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Log Aktivitas</h1>

            <!-- Deskripsi -->
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Pantau rekam jejak aktivitas operasional kasir, operator cuci, dan wewenang pemilik Fast Wash secara transparan, akuntabel, dan real-time tanpa celah manipulasi.
            </p>
        </div>

        <!-- Sisi Kanan: Status Sinkronisasi Real-time & Info Ledger Tamper-Proof -->
        <div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-3.5 shrink-0">
            
            <!-- Baris Atas: Badge Status Sync & Tombol Refresh -->
            <div class="flex items-center gap-2">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-slate-200/90 shadow-xs text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-600 font-medium">Audit Trail:</span>
                    <span class="text-emerald-700 font-bold">Real-time Sync Aktif</span>
                </div>

                <!-- Tombol Refresh / Sinkronisasi -->
                <button type="button"
                        onclick="location.reload()"
                        class="w-9 h-9 rounded-2xl bg-white border border-slate-200/90 hover:bg-slate-50 text-slate-600 flex items-center justify-center shadow-xs transition-colors focus:outline-hidden"
                        title="Perbarui log aktivitas">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>

            <!-- Baris Bawah: Kotak Integritas Log Ledger -->
            <div class="bg-white rounded-2xl p-3 border border-slate-100 shadow-xs flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-medium">Integritas Log Ledger</div>
                    <div class="text-xs font-bold text-slate-900">Immutable & Tamper-Proof</div>
                </div>
            </div>

        </div>

    </div>

    <!-- 2. Toolbar Filter & Pencarian (Sesuai Visual Figma: assets/img/Container.png) -->
    <form method="GET" action="<?php echo $base; ?>log-activity" id="form-filter-log" class="flex flex-col md:flex-row md:items-center gap-3">
        
        <!-- Input Pencarian -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <input type="text"
                   id="input-cari-log"
                   name="q"
                   value="<?php echo e($keyword); ?>"
                   placeholder="Cari pengguna, deskripsi, atau ID pesanan"
                   class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm text-slate-800 placeholder-slate-400 focus:outline-hidden focus:border-[#0085ff] focus:ring-2 focus:ring-[#0085ff]/20 transition-all shadow-xs">
        </div>

        <!-- Filter Peran (Role) -->
        <div class="shrink-0">
            <select id="select-role"
                    name="role"
                    class="w-full md:w-auto px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium text-slate-700 focus:outline-hidden focus:border-[#0085ff] focus:ring-2 focus:ring-[#0085ff]/20 transition-all shadow-xs cursor-pointer">
                <option value="" <?php echo $filterRole === '' ? 'selected' : ''; ?>>Semua Peran</option>
                <option value="pemilik" <?php echo strtolower($filterRole) === 'pemilik' ? 'selected' : ''; ?>>Pemilik</option>
                <option value="karyawan" <?php echo strtolower($filterRole) === 'karyawan' ? 'selected' : ''; ?>>Karyawan</option>
            </select>
        </div>

        <!-- Filter Kategori -->
        <div class="shrink-0">
            <select id="select-kategori"
                    name="kategori"
                    class="w-full md:w-auto px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium text-slate-700 focus:outline-hidden focus:border-[#0085ff] focus:ring-2 focus:ring-[#0085ff]/20 transition-all shadow-xs cursor-pointer">
                <option value="" <?php echo $filterKategori === '' ? 'selected' : ''; ?>>Semua Kategori</option>
                <option value="Status Pesanan" <?php echo $filterKategori === 'Status Pesanan' ? 'selected' : ''; ?>>Status Pesanan</option>
                <option value="Operasional" <?php echo $filterKategori === 'Operasional' ? 'selected' : ''; ?>>Operasional</option>
                <option value="Transaksi" <?php echo $filterKategori === 'Transaksi' ? 'selected' : ''; ?>>Transaksi</option>
                <option value="Keamanan" <?php echo $filterKategori === 'Keamanan' ? 'selected' : ''; ?>>Keamanan</option>
            </select>
        </div>

        <!-- Tombol Ekspor Log (Sesuai Tombol Biru di Container.png) -->
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    id="btn-ekspor-log"
                    class="w-full md:w-auto px-5 py-3 bg-[#0085ff] hover:bg-[#0073de] text-white text-sm font-semibold rounded-2xl shadow-sm shadow-[#0085ff]/30 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Ekspor Log</span>
            </button>

            <?php if ($filterRole !== '' || $filterKategori !== '' || $keyword !== ''): ?>
                <a href="<?php echo $base; ?>log-activity"
                   class="px-3.5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold rounded-2xl transition-colors flex items-center justify-center"
                   title="Reset semua filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>

    </form>

    <!-- 3. Tabel Data dengan Header Biru (#0085ff) (Sesuai Visual Figma: Container.png) -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-100 overflow-hidden">
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="tabel-log-aktivitas">
                
                <!-- Table Header Biru Solid (#0085ff) -->
                <thead>
                    <tr class="bg-[#0085ff] text-white text-sm font-bold">
                        <th class="py-4 px-6 text-white font-bold whitespace-nowrap">Tanggal</th>
                        <th class="py-4 px-6 text-white font-bold whitespace-nowrap">Pengguna & Peran</th>
                        <th class="py-4 px-6 text-white font-bold whitespace-nowrap">Kategori</th>
                        <th class="py-4 px-6 text-white font-bold">Deskripsi Aktivitas</th>
                        <th class="py-4 px-6 text-white font-bold whitespace-nowrap text-right sm:text-left">IP Adress</th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($pagedLogs)): ?>
                        <!-- Tampilan Data Kosong (Empty State) -->
                        <tr>
                            <td colspan="5" class="py-14 px-4 text-center">
                                <div class="max-w-sm mx-auto flex flex-col items-center">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                            <line x1="8" y1="11" x2="14" y2="11"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Rekaman Log</h3>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Tidak ditemukan catatan audit trail yang cocok dengan kata kunci atau filter yang dipilih.
                                    </p>
                                    <?php if ($filterRole !== '' || $filterKategori !== '' || $keyword !== ''): ?>
                                        <a href="<?php echo $base; ?>log-activity" class="mt-4 px-4 py-2 bg-blue-50 text-[#0085ff] text-xs font-bold rounded-xl hover:bg-blue-100 transition-colors">
                                            Hapus Filter
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagedLogs as $log): ?>
                            <?php
                                $isOwner = strtolower($log['role']) === 'pemilik';
                            ?>
                            <tr class="hover:bg-slate-50/70 transition-colors log-row"
                                data-role="<?php echo e(strtolower($log['role'])); ?>"
                                data-kategori="<?php echo e(strtolower($log['kategori'])); ?>"
                                data-desc="<?php echo e(strtolower($log['deskripsi'])); ?>"
                                data-kode="<?php echo e(strtolower($log['kode_pesanan'] ?? '')); ?>"
                                data-ip="<?php echo e($log['ip_address']); ?>"
                                data-user="<?php echo e(strtolower($log['nama_pengguna'])); ?>">
                                
                                <!-- Kolom 1: Tanggal & Waktu -->
                                <td class="py-5 px-6 align-top whitespace-nowrap">
                                    <div class="font-bold text-slate-800 text-sm"><?php echo e($log['tanggal']); ?></div>
                                    <div class="text-xs text-slate-400 mt-0.5 font-medium"><?php echo e($log['jam']); ?></div>
                                </td>

                                <!-- Kolom 2: Pengguna & Peran -->
                                <td class="py-5 px-6 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar Inisial Bulat Biru Muda -->
                                        <div class="w-9 h-9 rounded-full bg-blue-100 text-[#0085ff] font-bold text-xs flex items-center justify-center shrink-0">
                                            <?php echo e($log['inisial']); ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm"><?php echo e($log['nama_pengguna']); ?></div>
                                            <div class="mt-0.5">
                                                <?php if ($isOwner): ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                                        <span>Pemilik</span>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#0085ff]"></span>
                                                        <span>Karyawan</span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kolom 3: Kategori (Pill Biru Muda Sesuai Figma) -->
                                <td class="py-5 px-6 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-600 border border-sky-100">
                                        <?php echo e($log['kategori']); ?>
                                    </span>
                                </td>

                                <!-- Kolom 4: Deskripsi Aktivitas & Tag Meta -->
                                <td class="py-5 px-6 align-top">
                                    <div class="text-slate-800 text-sm leading-relaxed">
                                        <?php
                                            // Format highlight khusus jika ada kode pesanan (#FW...)
                                            $formattedDesc = e($log['deskripsi']);
                                            if (!empty($log['kode_pesanan'])) {
                                                $kp = e($log['kode_pesanan']);
                                                $formattedDesc = str_replace($kp, '<a href="' . $base . 'transaksi/status?q=' . urlencode($kp) . '" class="text-[#0085ff] font-bold hover:underline">' . $kp . '</a>', $formattedDesc);
                                            }
                                            // Format highlight status transisi jika ada
                                            if (!empty($log['status_baru'])) {
                                                $sb = e($log['status_baru']);
                                                $formattedDesc = str_replace($sb, '<span class="text-emerald-600 font-bold">' . $sb . '</span>', $formattedDesc);
                                            }
                                            // Format highlight nominal jika ada
                                            if (str_contains($formattedDesc, 'Rp 1.250.000')) {
                                                $formattedDesc = str_replace('Rp 1.250.000', '<span class="font-bold text-slate-900">Rp 1.250.000</span>', $formattedDesc);
                                            }
                                            echo $formattedDesc;
                                        ?>
                                    </div>

                                    <!-- Tags / Metadata Pills di Bawah Deskripsi (Sesuai Figma) -->
                                    <?php if (!empty($log['tags'])): ?>
                                        <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                            <?php foreach ($log['tags'] as $tag): ?>
                                                <?php
                                                    $isTagBlue = isset($log['tag_highlight']) && $log['tag_highlight'] === $tag;
                                                    $tagClass = $isTagBlue
                                                        ? 'bg-blue-50 text-[#0085ff] border border-blue-100/80 font-medium'
                                                        : 'bg-slate-100 text-slate-600 border border-transparent font-medium';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-lg text-xs <?php echo $tagClass; ?>">
                                                    <?php echo e($tag); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Kolom 5: IP Adress & Tombol Rincian -->
                                <td class="py-5 px-6 align-top whitespace-nowrap text-right sm:text-left">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-end sm:justify-start gap-2">
                                        <?php if (!empty($log['has_rincian'])): ?>
                                            <!-- Tombol Rincian dengan Ikon Mata (Sesuai Figma) -->
                                            <button type="button"
                                                    class="btn-lihat-rincian inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-blue-50 text-[#0085ff] border border-blue-100 hover:bg-blue-100 text-xs font-semibold transition-colors"
                                                    data-id="<?php echo e($log['id_log']); ?>"
                                                    data-user="<?php echo e($log['nama_pengguna']); ?>"
                                                    data-role="<?php echo e(ucfirst($log['role'])); ?>"
                                                    data-waktu="<?php echo e($log['tanggal'] . ' ' . $log['jam']); ?>"
                                                    data-ip="<?php echo e($log['ip_address']); ?>"
                                                    data-kategori="<?php echo e($log['kategori']); ?>"
                                                    data-desc="<?php echo e($log['deskripsi']); ?>">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>Rincian</span>
                                            </button>
                                        <?php else: ?>
                                            <!-- Teks IP Address untuk baris tanpa tombol rincian -->
                                            <span class="text-xs font-mono text-slate-400">
                                                <?php echo e($log['ip_address']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Paginasi Halaman (Sesuai Visual Figma: Container.png & Task 6.2) -->
        <div class="p-5 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white">
            
            <!-- Teks Ringkasan Sesuai Task 6.2 & Visual Figma: "Menampilkan X dari Y aktivitas hari ini" -->
            <div class="text-xs text-slate-500 font-medium">
                Menampilkan <span class="font-bold text-slate-900"><?php echo $countOnPage; ?></span> dari <span class="font-bold text-slate-900"><?php echo $totalLogs; ?></span> aktivitas hari ini
            </div>

            <!-- Tombol Navigasi Halaman -->
            <?php if ($totalPages > 1): ?>
                <nav class="flex items-center gap-1.5" aria-label="Navigasi Halaman">
                    
                    <!-- Tombol Sebelumnya -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base; ?>log-activity?page=<?php echo $page - 1; ?>&role=<?php echo urlencode($filterRole); ?>&kategori=<?php echo urlencode($filterKategori); ?>&q=<?php echo urlencode($keyword); ?>"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:bg-slate-50 transition-colors">
                            Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-3.5 py-2 rounded-xl border border-slate-200/60 bg-slate-50 text-slate-400 text-xs font-semibold cursor-not-allowed">
                            Sebelumnya
                        </span>
                    <?php endif; ?>

                    <!-- Nomor Halaman (1, 2, 3...) -->
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="w-9 h-9 rounded-xl bg-[#0085ff] text-white text-xs font-bold flex items-center justify-center shadow-xs shadow-[#0085ff]/30">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo $base; ?>log-activity?page=<?php echo $i; ?>&role=<?php echo urlencode($filterRole); ?>&kategori=<?php echo urlencode($filterKategori); ?>&q=<?php echo urlencode($keyword); ?>"
                                   class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-semibold flex items-center justify-center transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <!-- Tombol Berikutnya (Sesuai Teks di Container.png) -->
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $base; ?>log-activity?page=<?php echo $page + 1; ?>&role=<?php echo urlencode($filterRole); ?>&kategori=<?php echo urlencode($filterKategori); ?>&q=<?php echo urlencode($keyword); ?>"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-xs font-semibold hover:bg-slate-50 transition-colors">
                            Berikutnya
                        </a>
                    <?php else: ?>
                        <span class="px-3.5 py-2 rounded-xl border border-slate-200/60 bg-slate-50 text-slate-400 text-xs font-semibold cursor-not-allowed">
                            Berikutnya
                        </span>
                    <?php endif; ?>

                </nav>
            <?php endif; ?>

        </div>

    </div>

</div>

<!-- Modal Detail Rincian Log (Popup Interaktif) -->
<div id="modal-rincian-log" tabindex="-1" aria-hidden="true" class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden p-4 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
        
        <!-- Header Modal -->
        <div class="px-6 py-5 bg-[#0085ff] text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white leading-tight">Rincian Rekaman Audit</h3>
                    <p class="text-xs text-blue-100 mt-0.5">Jejak ledger immutable terverifikasi</p>
                </div>
            </div>
            <button type="button" id="btn-tutup-modal" class="text-white/80 hover:text-white p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-6 space-y-4 text-sm">
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                <div>
                    <div class="text-[11px] font-semibold text-slate-400">Pengguna & Peran</div>
                    <div class="font-bold text-slate-800 mt-0.5" id="modal-user">-</div>
                    <div class="text-xs text-slate-500" id="modal-role">-</div>
                </div>
                <div>
                    <div class="text-[11px] font-semibold text-slate-400">Waktu Kejadian</div>
                    <div class="font-bold text-slate-800 mt-0.5" id="modal-waktu">-</div>
                    <div class="text-xs text-slate-500 font-mono" id="modal-ip">-</div>
                </div>
            </div>

            <div>
                <div class="text-xs font-semibold text-slate-500 mb-1">Kategori Aktivitas</div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-600 border border-sky-100" id="modal-kategori">
                    -
                </span>
            </div>

            <div>
                <div class="text-xs font-semibold text-slate-500 mb-1">Deskripsi Lengkap</div>
                <div class="p-3.5 bg-white border border-slate-200 rounded-2xl text-slate-700 leading-relaxed font-normal" id="modal-desc">
                    -
                </div>
            </div>

            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-xs text-emerald-800 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Catatan ini tersimpan secara immutable dalam log ledger dan tidak dapat diubah oleh peran apa pun demi integritas sistem.</span>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" id="btn-tutup-modal-bawah" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                Tutup
            </button>
        </div>

    </div>
</div>

            <!-- Penutup Tag Kontainer Layout Bersama (Tanpa footer.php) -->
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
            <p class="font-bold text-slate-900" id="toast-demo-title">Ekspor Berhasil</p>
            <p class="text-xs text-slate-500 mt-0.5" id="toast-demo-message">Berkas audit trail berhasil disiapkan untuk diunduh (Simulasi).</p>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600 ml-auto p-1" onclick="document.getElementById('toast-demo').classList.add('hidden')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Skrip Vendor Flowbite & App JS -->
    <script src="<?php echo $base; ?>assets/vendor/flowbite.min.js"></script>
    <script src="<?php echo $base; ?>assets/js/app.js"></script>

    <!-- Skrip Interaktif: Live Filter, Modal Rincian, dan Ekspor -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputCari = document.getElementById('input-cari-log');
            const selectRole = document.getElementById('select-role');
            const selectKategori = document.getElementById('select-kategori');
            const formFilter = document.getElementById('form-filter-log');
            const btnEkspor = document.getElementById('btn-ekspor-log');
            const toastDemo = document.getElementById('toast-demo');

            // Otomatis submit saat dropdown filter diubah
            if (selectRole && formFilter) {
                selectRole.addEventListener('change', function () {
                    formFilter.submit();
                });
            }
            if (selectKategori && formFilter) {
                selectKategori.addEventListener('change', function () {
                    formFilter.submit();
                });
            }

            // Live filter instan saat mengetik di input pencarian (debounce 350ms)
            if (inputCari && formFilter) {
                let debounceTimeout = null;
                inputCari.addEventListener('input', function () {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(function () {
                        formFilter.submit();
                    }, 400);
                });
            }

            // Handler tombol Ekspor Log
            if (btnEkspor && toastDemo) {
                btnEkspor.addEventListener('click', function () {
                    document.getElementById('toast-demo-title').textContent = 'Ekspor Log Disimulasikan';
                    document.getElementById('toast-demo-message').textContent = 'Audit trail siap diekspor ke format CSV / Ledger. Fitur ini beroperasi dalam mode pratinjau.';
                    toastDemo.classList.remove('hidden');
                    setTimeout(function () {
                        toastDemo.classList.add('hidden');
                    }, 4000);
                });
            }

            // Handler Modal Rincian
            const modal = document.getElementById('modal-rincian-log');
            const btnTutup = document.getElementById('btn-tutup-modal');
            const btnTutupBawah = document.getElementById('btn-tutup-modal-bawah');

            document.querySelectorAll('.btn-lihat-rincian').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('modal-user').textContent = this.dataset.user;
                    document.getElementById('modal-role').textContent = this.dataset.role;
                    document.getElementById('modal-waktu').textContent = this.dataset.waktu;
                    document.getElementById('modal-ip').textContent = 'IP: ' + this.dataset.ip;
                    document.getElementById('modal-kategori').textContent = this.dataset.kategori;
                    document.getElementById('modal-desc').textContent = this.dataset.desc;
                    modal.classList.remove('hidden');
                });
            });

            const closeModal = function () {
                modal.classList.add('hidden');
            };

            if (btnTutup) btnTutup.addEventListener('click', closeModal);
            if (btnTutupBawah) btnTutupBawah.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModal();
                });
            }
        });
    </script>
</body>
</html>
