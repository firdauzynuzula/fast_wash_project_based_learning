<?php
date_default_timezone_set('Asia/Jakarta');

$hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$sekarang     = new DateTime();
$tanggalPanjang = $hari[(int) $sekarang->format('w')] . ', ' . $sekarang->format('j') . ' ' . $bulan[(int) $sekarang->format('n')] . ' ' . $sekarang->format('Y');
$jamSekarang  = $sekarang->format('H.i.s');

// Data contoh (nanti ganti dengan query database)
$checkIn  = '07:58';
$checkOut = null; // isi jam check-out kalau sudah check-out, mis. '17:04'

$absensi = [
    ['tanggal' => 'Sab, 17 Okt', 'masuk' => '07:58', 'keluar' => '17:04', 'durasi' => '9 jam 6 menit',  'status' => 'Hadir'],
    ['tanggal' => 'Jum, 16 Okt', 'masuk' => '07:45', 'keluar' => '17:12', 'durasi' => '9 jam 12 menit', 'status' => 'Hadir'],
    ['tanggal' => 'Kam, 15 Okt', 'masuk' => '07:52', 'keluar' => '17:06', 'durasi' => '9 jam 8 menit',  'status' => 'Hadir'],
    ['tanggal' => 'Rab, 14 Okt', 'masuk' => '08:10', 'keluar' => '17:15', 'durasi' => '9 jam 5 menit',  'status' => 'Terlambat'],
    ['tanggal' => 'Sel, 13 Okt', 'masuk' => '07:49', 'keluar' => '17:12', 'durasi' => '9 jam 20 menit', 'status' => 'Hadir'],
    ['tanggal' => 'Sen, 12 Okt', 'masuk' => '07:55', 'keluar' => '17:08', 'durasi' => '9 jam 13 menit', 'status' => 'Hadir'],
    ['tanggal' => 'Min, 11 Okt', 'masuk' => '-',     'keluar' => '-',     'durasi' => '-',             'status' => 'Izin'],
];

$badgeStatus = [
    'Hadir'     => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'Terlambat' => 'border-amber-200 bg-amber-50 text-amber-700',
    'Izin'      => 'border-blue-200 bg-blue-50 text-blue-600',
];

$totalData    = 14;
$perPage      = 5;
$totalHalaman = (int) ceil($totalData / $perPage);
$halaman      = isset($_GET['page']) ? max(1, min($totalHalaman, (int) $_GET['page'])) : 1;

$menu = [
    ['label' => 'Dasbor',        'href' => '../dashboard/index.php',    'aktif' => false, 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],
    ['label' => 'Pesanan',       'href' => '../pesanan/index.php',      'aktif' => false, 'icon' => '<path d="M8 3 3 6l2 4 3-1v12h8V9l3 1 2-4-5-3a4 4 0 0 1-8 0Z"/>'],
    ['label' => 'Pelanggan',     'href' => '../pelanggan/index.php',    'aktif' => false, 'icon' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.5A6.5 6.5 0 0 1 21.5 20"/>'],
    ['label' => 'Pembayaran',    'href' => '../pembayaran/index.php',   'aktif' => false, 'icon' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/>'],
    ['label' => 'Laporan',       'href' => '../laporan/index.php',      'aktif' => false, 'icon' => '<path d="M3 3v18h18"/><path d="m7 15 4-5 3 3 5-6"/>'],
    ['label' => 'Absensi',       'href' => 'index.php',                 'aktif' => true,  'icon' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/><path d="m9.5 15.5 2 2 3.5-4"/>'],
    ['label' => 'Log Aktivitas', 'href' => '../log-activity/index.php', 'aktif' => false, 'icon' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5"/><path d="M3.5 3.5v5h5"/><path d="M12 7.5V12l3 2"/>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>Absensi | Fast Wash</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="font-jakarta bg-[#f1f6fb] text-slate-900 antialiased">

    <!-- Header -->
    <header class="sticky top-0 z-30 flex h-18 items-center bg-white">
        <a href="../dashboard/index.php" class="flex h-full shrink-0 items-center gap-3 px-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#0085ff]/40 lg:w-72 lg:border-r lg:border-slate-200/70 lg:px-7">
            <span class="flex size-11 items-center justify-center rounded-xl bg-[#0085ff] text-white shadow-md shadow-[#0085ff]/30">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="2.5" width="16" height="19" rx="3"/>
                    <circle cx="12" cy="13.5" r="4.5"/>
                    <path d="M10.5 14.5a2 2 0 0 0 3-1.5"/>
                    <path d="M8 6h.01M11 6h.01"/>
                </svg>
            </span>
            <span class="text-2xl font-extrabold tracking-tight text-[#0085ff]">Fast Wash</span>
        </a>

        <div class="flex min-w-0 flex-1 items-center justify-between gap-4 px-5 lg:px-8">
            <nav aria-label="Breadcrumb" class="hidden items-center gap-3 text-sm font-semibold sm:flex">
                <a href="index.php" class="text-[#0085ff] hover:underline">Absensi</a>
                <span class="size-1.5 rounded-full bg-slate-300" aria-hidden="true"></span>
                <span class="text-slate-600" aria-current="page"><?= $tanggalPanjang ?></span>
            </nav>

            <div class="ml-auto flex items-center gap-3">
                <button type="button" class="relative flex size-10 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-600 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40" aria-label="Notifikasi">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9a6 6 0 1 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9Z"/>
                        <path d="M10 20a2 2 0 0 0 4 0"/>
                    </svg>
                    <span class="absolute right-2 top-2 size-2.5 rounded-full bg-amber-400 ring-2 ring-white"></span>
                </button>

                <div class="flex items-center gap-3 rounded-full border border-slate-200/70 bg-white p-1.5 pr-4 shadow-sm">
                    <span class="relative flex size-8 items-center justify-center rounded-full bg-[#0085ff] text-xs font-bold text-white">
                        A
                        <span class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </span>
                    <span class="text-sm font-semibold text-slate-700">Pemilik</span>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">

        <!-- Sidebar -->
        <aside class="sticky top-18 hidden h-[calc(100vh-4.5rem)] w-72 shrink-0 flex-col border-r border-slate-200/70 bg-white lg:flex">
            <!-- Navigasi -->
            <nav class="mt-6 px-5" aria-label="Menu utama">
                <ul class="space-y-1.5">
                    <?php foreach ($menu as $item): ?>
                        <li>
                            <a href="<?= $item['href'] ?>"
                               <?= $item['aktif'] ? 'aria-current="page"' : '' ?>
                               class="flex items-center gap-4 rounded-2xl px-4 py-3.5 text-[15px] font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40
                               <?= $item['aktif']
                                    ? 'bg-[#0085ff] text-white shadow-md shadow-[#0085ff]/25'
                                    : 'text-slate-600 hover:bg-[#e8f3ff] hover:text-[#0085ff]' ?>">
                                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <?= $item['icon'] ?>
                                </svg>
                                <?= $item['label'] ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Profil + keluar -->
            <div class="mt-auto px-5 pb-6">
                <div class="flex items-center gap-3 rounded-2xl bg-[#eaf2fe] px-3 py-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#a8d4ff] text-sm font-bold text-[#0085ff]">A</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900">Admin</p>
                        <p class="truncate text-xs text-slate-500">owner@fastwash.com</p>
                    </div>
                </div>

                <a href="../auth/logout.php" class="mt-5 flex w-fit items-center gap-3 rounded px-3 text-[15px] font-medium text-slate-600 hover:text-[#0085ff] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/40">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <!-- Konten -->
        <div class="min-w-0 flex-1">
            <main class="space-y-7 px-5 pb-10 pt-8 lg:px-8">

                <!-- Jam & check-in/out -->
                <section class="rounded-[2rem] bg-white px-6 py-10 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:py-12" aria-labelledby="judul-jam">
                    <div class="mx-auto flex max-w-160 flex-col items-center">
                        <p id="judul-jam" class="text-base font-medium text-slate-500"><?= $tanggalPanjang ?></p>
                        <p id="jam" class="mt-2 text-6xl font-extrabold tracking-tight text-[#2563eb] tabular-nums sm:text-7xl" aria-live="off"><?= $jamSekarang ?></p>

                        <div class="mt-8 grid w-full gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl border border-emerald-200/70 bg-[#f0fdf6] px-6 py-5">
                                <p class="text-sm font-semibold text-emerald-900">Check-in</p>
                                <p class="mt-1 text-3xl font-extrabold tracking-tight text-emerald-600"><?= $checkIn ?? '--:--' ?></p>
                            </div>
                            <div class="rounded-3xl border border-slate-200/70 bg-slate-50 px-6 py-5">
                                <p class="text-sm font-semibold text-slate-500">Check-out</p>
                                <p class="mt-1 text-3xl font-extrabold tracking-tight <?= $checkOut ? 'text-slate-700' : 'text-slate-300' ?>"><?= $checkOut ?? '--:--' ?></p>
                            </div>
                        </div>

                        <?php if (!$checkOut): ?>
                            <form action="" method="post" class="mt-6 w-full">
                                <button type="submit" name="aksi" value="checkout"
                                        class="flex h-14 w-full items-center justify-center gap-3 rounded-3xl bg-[#e11d48] text-lg font-semibold text-white shadow-lg shadow-[#e11d48]/25 transition hover:bg-[#be123c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e11d48]/50 focus-visible:ring-offset-2">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                        <path d="M16 17l5-5-5-5"/>
                                        <path d="M21 12H9"/>
                                    </svg>
                                    Check-out
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Riwayat absensi -->
                <section class="rounded-[2rem] bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]" aria-label="Riwayat absensi">

                    <!-- Filter -->
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <label class="relative block w-full sm:max-w-100">
                            <span class="sr-only">Cari status kehadiran</span>
                            <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input id="cari" type="search" placeholder="Cari status kehadiran"
                                   class="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-12 pr-4 text-[15px] text-slate-700 placeholder:text-slate-400 focus:border-[#0085ff] focus:outline-none focus:ring-2 focus:ring-[#0085ff]/20">
                        </label>

                        <label class="relative block w-full sm:w-48">
                            <span class="sr-only">Filter status</span>
                            <select id="status" class="h-12 w-full appearance-none rounded-2xl border border-slate-200 bg-white pl-4 pr-10 text-[15px] text-slate-700 focus:border-[#0085ff] focus:outline-none focus:ring-2 focus:ring-[#0085ff]/20">
                                <option value="">Semua Status</option>
                                <option value="hadir">Hadir</option>
                                <option value="terlambat">Terlambat</option>
                                <option value="izin">Izin</option>
                            </select>
                            <svg class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </label>
                    </div>

                    <!-- Tabel -->
                    <div class="mt-5 overflow-hidden rounded-2xl">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-175 text-center text-[15px]">
                                <thead class="bg-[#0085ff] text-white">
                                    <tr>
                                        <th scope="col" class="py-5 pl-8 pr-4 text-left font-medium">Tanggal</th>
                                        <th scope="col" class="px-4 py-5 font-medium">Jam Masuk</th>
                                        <th scope="col" class="px-4 py-5 font-medium">Jam Keluar</th>
                                        <th scope="col" class="px-4 py-5 font-medium">Durasi Kehadiran</th>
                                        <th scope="col" class="px-4 py-5 pr-8 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="isi-tabel">
                                    <?php foreach ($absensi as $a): ?>
                                        <tr data-status="<?= strtolower($a['status']) ?>" class="transition hover:bg-slate-50/70">
                                            <td class="py-5 pl-8 pr-4 text-left text-slate-700"><?= htmlspecialchars($a['tanggal']) ?></td>
                                            <td class="px-4 py-5 text-slate-700"><?= htmlspecialchars($a['masuk']) ?></td>
                                            <td class="px-4 py-5 text-slate-700"><?= htmlspecialchars($a['keluar']) ?></td>
                                            <td class="px-4 py-5 text-slate-700"><?= htmlspecialchars($a['durasi']) ?></td>
                                            <td class="px-4 py-5 pr-8">
                                                <span class="inline-block rounded-full border px-3.5 py-1 text-sm font-medium <?= $badgeStatus[$a['status']] ?? 'border-slate-200 bg-slate-50 text-slate-600' ?>">
                                                    <?= htmlspecialchars($a['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="kosong" class="hidden">
                                        <td colspan="5" class="py-10 text-slate-500">Tidak ada data absensi yang cocok.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginasi -->
                        <div class="flex flex-col gap-4 border-t border-slate-100 bg-slate-50/80 px-8 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-slate-500">
                                Menampilkan <span class="font-bold text-slate-700"><?= $perPage ?></span>
                                dari <span class="font-bold text-slate-700"><?= $totalData ?></span> Absensi
                            </p>

                            <nav aria-label="Paginasi" class="flex items-center gap-2">
                                <?php if ($halaman > 1): ?>
                                    <a href="?page=<?= $halaman - 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Sebelumnya</a>
                                <?php else: ?>
                                    <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Sebelumnya</span>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                                    <?php if ($i === $halaman): ?>
                                        <span aria-current="page" class="flex size-10 items-center justify-center rounded-xl bg-[#0085ff] text-sm font-semibold text-white"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?>" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($halaman < $totalHalaman): ?>
                                    <a href="?page=<?= $halaman + 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Berikutnya</a>
                                <?php else: ?>
                                    <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Berikutnya</span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        // Jam berjalan (format 02.35.12)
        const jam = document.getElementById('jam');
        function updateJam() {
            const d = new Date();
            const dua = (n) => String(n).padStart(2, '0');
            jam.textContent = dua(d.getHours()) + '.' + dua(d.getMinutes()) + '.' + dua(d.getSeconds());
        }
        setInterval(updateJam, 1000);

        // Filter tabel: cari teks + filter status
        const cari = document.getElementById('cari');
        const status = document.getElementById('status');
        const baris = document.querySelectorAll('#isi-tabel tr[data-status]');
        const kosong = document.getElementById('kosong');

        function saring() {
            const kata = cari.value.trim().toLowerCase();
            const pilih = status.value;
            let tampil = 0;
            baris.forEach((tr) => {
                const cocokTeks = tr.textContent.toLowerCase().includes(kata);
                const cocokStatus = !pilih || tr.dataset.status === pilih;
                const lihat = cocokTeks && cocokStatus;
                tr.classList.toggle('hidden', !lihat);
                if (lihat) tampil++;
            });
            kosong.classList.toggle('hidden', tampil !== 0);
        }
        cari.addEventListener('input', saring);
        status.addEventListener('change', saring);
    </script>
</body>
</html>
