<?php
/**
 * Fast Wash - Modul Absensi: Lapisan View
 * Template antarmuka pengguna berbasis Flowbite UI & Tailwind CSS:
 * Jam digital server, kartu status check-in/out, modal konfirmasi, dropdown filter, dan tabel riwayat presensi.
 */

// Inisialisasi variabel defensif (mencegah galat PHP Notice / Warning: Undefined variable)
$base         = $base ?? './';
$route        = $route ?? 'absensi';
$hariIni      = $hariIni ?? date('Y-m-d');
$today        = $today ?? null;
$sudahMasuk   = $sudahMasuk ?? ($today && !empty($today['jam_masuk']));
$sudahKeluar  = $sudahKeluar ?? ($today && !empty($today['jam_pulang']));
$pesan        = $pesan ?? '';
$q            = $q ?? '';
$status       = $status ?? '';
$riwayat      = $riwayat ?? [];
$totalData    = $totalData ?? count($riwayat);
$totalHalaman = $totalHalaman ?? 1;
$page         = $page ?? 1;

// Helper lokal untuk antarmuka
if (!function_exists('absensi_tgl_indo')) {
    function absensi_tgl_indo($tgl, $panjang = false): string
    {
        if (!$tgl) return '-';
        $hari  = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $hariP = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan  = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulanP = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                   'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $t = strtotime((string) $tgl);
        $w = (int) date('w', $t);
        $m = (int) date('n', $t);
        if ($panjang) {
            return $hariP[$w] . ', ' . date('j', $t) . ' ' . $bulanP[$m] . ' ' . date('Y', $t);
        }
        return $hari[$w] . ', ' . date('j', $t) . ' ' . $bulan[$m];
    }
}

if (!function_exists('absensi_durasi')) {
    function absensi_durasi(?string $masuk, ?string $keluar): string
    {
        if (!$masuk || !$keluar) return '-';
        $selisih = strtotime($keluar) - strtotime($masuk);
        if ($selisih < 0) return '-';
        $jam = floor($selisih / 3600);
        $menit = floor(($selisih % 3600) / 60);
        return $jam . ' jam ' . $menit . ' menit';
    }
}

if (!function_exists('absensi_jam')) {
    function absensi_jam(?string $j): string
    {
        return $j ? date('H:i', strtotime($j)) : '-';
    }
}

if (!function_exists('absensi_url_halaman')) {
    function absensi_url_halaman(int $p): string
    {
        $qs = $_GET;
        $qs['page'] = $p;
        return '?' . http_build_query($qs);
    }
}

// Pemetaan kelas badge Flowbite berdasarkan status presensi
$badgeClasses = [
    'Hadir'     => 'bg-green-50 text-green-700 border-green-200',
    'Terlambat' => 'bg-amber-50 text-amber-700 border-amber-200',
    'Izin'      => 'bg-blue-50 text-blue-700 border-blue-200',
    'Sakit'     => 'bg-purple-50 text-purple-700 border-purple-200',
    'Alpha'     => 'bg-red-50 text-red-700 border-red-200',
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu Jam Server & Status Presensi Hari Ini -->
<div class="p-6 sm:p-8 bg-white border border-gray-200 rounded-2xl shadow-xs text-center mb-6">
    <p class="text-sm font-medium text-gray-500 mb-1"><?php echo e(absensi_tgl_indo($hariIni, true)); ?></p>

    <!-- Jam Berjalan (Realtime WIB via Sinkronisasi Server PHP) -->
    <p id="jam-digital" class="text-5xl sm:text-6xl font-extrabold text-[#0085ff] tracking-tight tabular-nums my-3">
        <?php echo date('H.i.s'); ?>
    </p>

    <!-- Kotak Indikator Check-in & Check-out -->
    <div class="grid grid-cols-2 gap-4 max-w-md mx-auto my-6">
        <div class="p-4 rounded-xl border transition-colors text-left <?php echo $sudahMasuk ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'; ?>">
            <p class="text-xs font-semibold uppercase tracking-wider mb-1 <?php echo $sudahMasuk ? 'text-green-700' : 'text-gray-500'; ?>">
                Check-in
            </p>
            <p class="text-2xl font-bold <?php echo $sudahMasuk ? 'text-green-800' : 'text-gray-400'; ?>">
                <?php echo $sudahMasuk ? e(absensi_jam($today['jam_masuk'] ?? null)) : '--:--'; ?>
            </p>
        </div>

        <div class="p-4 rounded-xl border transition-colors text-left <?php echo $sudahKeluar ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50'; ?>">
            <p class="text-xs font-semibold uppercase tracking-wider mb-1 <?php echo $sudahKeluar ? 'text-red-700' : 'text-gray-500'; ?>">
                Check-out
            </p>
            <p class="text-2xl font-bold <?php echo $sudahKeluar ? 'text-red-800' : 'text-gray-400'; ?>">
                <?php echo $sudahKeluar ? e(absensi_jam($today['jam_pulang'] ?? null)) : '--:--'; ?>
            </p>
        </div>
    </div>

    <!-- Alert Notifikasi / Pesan Feedback -->
    <?php if (!empty($pesan)): ?>
        <div class="flex items-center p-4 mb-5 text-sm text-red-800 rounded-xl bg-blue-50 border border-blue-200 max-w-md mx-auto" role="alert">
            <svg class="shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <div class="text-left"><?php echo e($pesan); ?></div>
        </div>
    <?php endif; ?>

    <!-- Tombol Aksi Check-in / Check-out -->
    <?php if (!$sudahMasuk): ?>
        <form action="<?php echo e($base); ?>absensi/proses" method="post" class="max-w-md mx-auto">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="aksi" value="checkin">
            <button type="submit" class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-semibold rounded-xl text-base px-6 py-3.5 inline-flex items-center justify-center gap-2 shadow-sm transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                </svg>
                <span>Check-in</span>
            </button>
        </form>
    <?php elseif (!$sudahKeluar): ?>
        <!-- Karyawan sudah check-in saat login -> tombol membuka modal konfirmasi Flowbite untuk mencegah salah klik -->
        <div class="max-w-md mx-auto">
            <button type="button"
                    id="btn-buka-modal-checkout"
                    data-modal-target="modal-konfirmasi-checkout"
                    data-modal-toggle="modal-konfirmasi-checkout"
                    class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-semibold rounded-xl text-base px-6 py-3.5 inline-flex items-center justify-center gap-2 shadow-sm transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                <span>Check-out</span>
            </button>
        </div>
    <?php else: ?>
        <div class="max-w-md mx-auto">
            <button type="button" disabled class="w-full text-gray-400 bg-gray-100 border border-gray-200 font-semibold rounded-xl text-base px-6 py-3.5 inline-flex items-center justify-center cursor-not-allowed">
                Absensi hari ini selesai
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Flowbite: Check-out Pulang (Mencegah Salah Klik) -->
<div id="modal-konfirmasi-checkout" tabindex="-1" aria-hidden="true"
     class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden p-4 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <!-- Tombol Tutup X -->
        <button type="button"
                id="btn-tutup-modal-x"
                data-modal-hide="modal-konfirmasi-checkout"
                class="absolute top-3 end-3 text-gray-400 bg-transparent hover:bg-gray-100 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center cursor-pointer">
            <svg class="w-3.5 h-3.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
            <span class="sr-only">Tutup modal</span>
        </button>

        <!-- Isi Modal -->
        <div class="p-6 text-center">
            <div class="w-14 h-14 rounded-full bg-red-50 text-red-600 mx-auto mb-4 flex items-center justify-center border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h3 class="mb-2 text-lg font-bold text-gray-900">Konfirmasi Check-out Pulang</h3>
            <p class="mb-6 text-sm text-gray-500 leading-relaxed">
                Apakah Anda yakin ingin melakukan <strong>Check-out</strong> sekarang? Setelah check-out dicatat, presensi shift hari ini akan terkunci dan tidak dapat diulang.
            </p>

            <!-- Formulir Eksekusi Check-out ke Server -->
            <form action="<?php echo e($base); ?>absensi/proses" method="post" class="flex items-center justify-center gap-3">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="aksi" value="checkout">
                <button type="button"
                        id="btn-batal-checkout"
                        data-modal-hide="modal-konfirmasi-checkout"
                        class="py-2.5 px-5 text-sm font-medium text-gray-700 focus:outline-none bg-white rounded-xl border border-gray-300 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-4 focus:ring-gray-100 cursor-pointer transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-semibold rounded-xl text-sm inline-flex items-center px-5 py-2.5 text-center shadow-sm cursor-pointer transition-colors">
                    Ya, Check-out Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Kartu Tabel Riwayat Kehadiran -->
<div class="bg-white border border-gray-200 rounded-2xl shadow-xs overflow-hidden px">
    <!-- Filter Pencarian & Status (Flowbite Search Input & Flowbite Dropdown) -->
    <form method="get" action="<?php echo e($base); ?>absensi" id="form-filter-absensi" class="p-5 flex flex-col sm:flex-row gap-3 border-b border-gray-100 items-stretch sm:items-center">
        <!-- Input Pencarian Flowbite -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
            </div>
            <input type="text" name="q" value="<?php echo e($q); ?>"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-[#0085ff] focus:border-[#0085ff] block w-full ps-10 p-2.5 transition-colors"
                   placeholder="Cari status kehadiran">
        </div>

        <!-- Hidden input untuk nilai status filter yang dipilih dari Flowbite Dropdown -->
        <input type="hidden" name="status" id="input-filter-status" value="<?php echo e($status); ?>">

        <!-- Flowbite Dropdown Component untuk Filter Status -->
        <div class="relative sm:w-48 shrink-0">
            <button id="dropdownStatusButton"
                    data-dropdown-toggle="dropdownStatusMenu"
                    type="button"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-800 hover:bg-gray-100 hover:text-gray-900 focus:ring-4 focus:ring-blue-100 font-medium rounded-xl text-sm px-4 py-2.5 text-center inline-flex items-center justify-between transition-colors cursor-pointer">
                <span class="truncate">
                    <?php echo !empty($status) ? e(ucfirst(strtolower($status))) : 'Semua Status'; ?>
                </span>
                <svg class="w-2.5 h-2.5 ms-2 text-gray-500 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                </svg>
            </button>

            <!-- Menu Konten Dropdown Flowbite -->
            <div id="dropdownStatusMenu"
                 class="z-20 hidden bg-white divide-y divide-gray-100 rounded-xl shadow-lg border border-gray-100 w-full sm:w-48 overflow-hidden absolute top-full mt-1.5 start-0">
                <ul class="py-2 text-sm text-gray-700 font-medium" aria-labelledby="dropdownStatusButton">
                    <li>
                        <button type="button"
                                onclick="absensiPilihStatus('')"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 hover:text-[#0085ff] flex items-center justify-between transition-colors cursor-pointer <?php echo empty($status) ? 'text-[#0085ff] bg-blue-50/50 font-semibold' : ''; ?>">
                            <span>Semua Status</span>
                            <?php if (empty($status)): ?>
                                <svg class="w-4 h-4 text-[#0085ff]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?php endif; ?>
                        </button>
                    </li>
                    <?php foreach (['Hadir', 'Terlambat', 'Izin'] as $s): ?>
                        <li>
                            <button type="button"
                                    onclick="absensiPilihStatus('<?php echo $s; ?>')"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-50 hover:text-[#0085ff] flex items-center justify-between transition-colors cursor-pointer <?php echo strcasecmp($status, $s) === 0 ? 'text-[#0085ff] bg-blue-50/50 font-semibold' : ''; ?>">
                                <span><?php echo $s; ?></span>
                                <?php if (strcasecmp($status, $s) === 0): ?>
                                    <svg class="w-4 h-4 text-[#0085ff]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </form>

    <!-- Tabel Flowbite -->
    <div class="relative overflow-x-auto rounded-3xl">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500">
            <thead class="text-xs text-white uppercase bg-[#0085ff]">
                <tr>
                    <th scope="col" class="px-6 py-3.5 text-center font-semibold">Tanggal</th>
                    <th scope="col" class="px-6 py-3.5 text-center font-semibold">Jam Masuk</th>
                    <th scope="col" class="px-6 py-3.5 text-center font-semibold">Jam Keluar</th>
                    <th scope="col" class="px-6 py-3.5 text-center font-semibold">Durasi Kehadiran</th>
                    <th scope="col" class="px-6 py-3.5 text-center font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php if (empty($riwayat)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                        Belum ada riwayat absensi.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($riwayat as $r):
                    $label = ucfirst(strtolower((string) ($r['status'] ?? '')));
                ?>
                    <tr class="bg-white hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-center font-medium text-gray-900 whitespace-nowrap">
                            <?php echo e(absensi_tgl_indo($r['tanggal'] ?? null)); ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-700">
                            <?php echo e(absensi_jam($r['jam_masuk'] ?? null)); ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-700">
                            <?php echo e(absensi_jam($r['jam_pulang'] ?? null)); ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600">
                            <?php echo e(absensi_durasi($r['jam_masuk'] ?? null, $r['jam_pulang'] ?? null)); ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="<?php echo $badgeClasses[$label] ?? 'bg-gray-50 text-gray-700 border-gray-200'; ?> text-xs font-medium px-3 py-1 rounded-full border inline-flex items-center gap-1">
                                <?php echo e($label); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi & Keterangan Flowbite -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 sm:p-5 bg-gray-50/50 border-t border-gray-100 text-xs sm:text-sm text-gray-500">
        <span class="text-gray-600">
            Menampilkan <span class="font-semibold text-gray-900"><?php echo count($riwayat); ?></span> dari <span class="font-semibold text-gray-900"><?php echo $totalData; ?></span> Absensi
        </span>
        <nav aria-label="Navigasi Halaman" class="inline-flex -space-x-px text-sm rounded-lg shadow-2xs">
            <!-- Tombol Sebelumnya -->
            <?php if ($page > 1): ?>
                <a href="<?php echo absensi_url_halaman($page - 1); ?>" class="flex items-center justify-center px-3.5 h-9 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 transition-colors">
                    Sebelumnya
                </a>
            <?php else: ?>
                <span class="flex items-center justify-center px-3.5 h-9 ms-0 leading-tight text-gray-300 bg-gray-50 border border-e-0 border-gray-200 rounded-s-lg cursor-not-allowed">
                    Sebelumnya
                </span>
            <?php endif; ?>

            <!-- Nomor Halaman -->
            <?php for ($p = 1; $p <= $totalHalaman; $p++): ?>
                <?php if ($p === $page): ?>
                    <span aria-current="page" class="flex items-center justify-center px-3.5 h-9 text-[#0085ff] border border-gray-300 bg-blue-50 font-semibold">
                        <?php echo $p; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo absensi_url_halaman($p); ?>" class="flex items-center justify-center px-3.5 h-9 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 transition-colors">
                        <?php echo $p; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Tombol Berikutnya -->
            <?php if ($page < $totalHalaman): ?>
                <a href="<?php echo absensi_url_halaman($page + 1); ?>" class="flex items-center justify-center px-3.5 h-9 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 transition-colors">
                    Berikutnya
                </a>
            <?php else: ?>
                <span class="flex items-center justify-center px-3.5 h-9 leading-tight text-gray-300 bg-gray-50 border border-gray-200 rounded-e-lg cursor-not-allowed">
                    Berikutnya
                </span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<!-- Sinkronisasi Jam Digital & Interaktivitas Komponen Flowbite (Modal & Dropdown) -->
<script>
(function () {
    // 1. Sinkronisasi Jam Digital Realtime WIB
    var serverMs = <?php echo (int) (microtime(true) * 1000); ?>;
    var offset   = serverMs - Date.now();
    var el       = document.getElementById('jam-digital');

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var now = new Date(Date.now() + offset);
        var wib = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
        if (el) {
            el.textContent = pad(wib.getHours()) + '.' + pad(wib.getMinutes()) + '.' + pad(wib.getSeconds());
        }
    }

    tick();
    setInterval(tick, 1000);

    // 2. Kontrol Modal Konfirmasi Check-out (Dukungan Tambahan Vanilla JS untuk Kehandalan Maksimal)
    var modalCheckout = document.getElementById('modal-konfirmasi-checkout');
    var btnBukaModal  = document.getElementById('btn-buka-modal-checkout');
    var btnTutupX     = document.getElementById('btn-tutup-modal-x');
    var btnBatal      = document.getElementById('btn-batal-checkout');

    function bukaModal() {
        if (modalCheckout) {
            modalCheckout.classList.remove('hidden');
        }
    }

    function tutupModal() {
        if (modalCheckout) {
            modalCheckout.classList.add('hidden');
        }
    }

    if (btnBukaModal) btnBukaModal.addEventListener('click', bukaModal);
    if (btnTutupX) btnTutupX.addEventListener('click', tutupModal);
    if (btnBatal) btnBatal.addEventListener('click', tutupModal);

    if (modalCheckout) {
        modalCheckout.addEventListener('click', function (e) {
            if (e.target === modalCheckout) {
                tutupModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalCheckout && !modalCheckout.classList.contains('hidden')) {
            tutupModal();
        }
    });

    // 3. Dropdown Filter Status (Flowbite Dropdown Helper & Fallback Handler)
    window.absensiPilihStatus = function (val) {
        var input = document.getElementById('input-filter-status');
        if (input) {
            input.value = val;
            if (input.form) {
                input.form.submit();
            }
        }
    };

    var btnDropdown = document.getElementById('dropdownStatusButton');
    var menuDropdown = document.getElementById('dropdownStatusMenu');

    if (btnDropdown && menuDropdown) {
        btnDropdown.addEventListener('click', function (e) {
            e.stopPropagation();
            menuDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!btnDropdown.contains(e.target) && !menuDropdown.contains(e.target)) {
                menuDropdown.classList.add('hidden');
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
