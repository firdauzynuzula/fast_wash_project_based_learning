<?php
date_default_timezone_set('Asia/Jakarta');

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function rupiah(int $angka, bool $spasi = true): string
{
    return 'Rp' . ($spasi ? ' ' : '') . number_format($angka, 0, ',', '.');
}

function tanggalPanjang(string $ymd, array $bulan): string
{
    $t = DateTime::createFromFormat('Y-m-d', $ymd) ?: new DateTime();
    return $t->format('j') . ' ' . $bulan[(int) $t->format('n')] . ' ' . $t->format('Y');
}

// Filter periode (contoh default sesuai desain)
$periodeDipilih = $_GET['periode'] ?? 'bulanan';
$mulai = $_GET['mulai'] ?? '2026-10-01';
$akhir = $_GET['akhir'] ?? '2026-10-31';
$teksPeriode = tanggalPanjang($mulai, $bulan) . ' – ' . tanggalPanjang($akhir, $bulan);

// Data contoh pesanan dalam periode (nanti ganti dengan query database)
$pesanan = [
    ['no' => '#FW24042', 'layanan' => 'Cuci + Setrika', 'jumlah' => 45000, 'status' => 'Belum bayar'],
    ['no' => '#FW24041', 'layanan' => 'Cuci Kering',    'jumlah' => 24000, 'status' => 'Lunas'],
    ['no' => '#FW24040', 'layanan' => 'Setrika',        'jumlah' => 40000, 'status' => 'Lunas'],
    ['no' => '#FW24039', 'layanan' => 'Cuci + Setrika', 'jumlah' => 70000, 'status' => 'Belum bayar'],
    ['no' => '#FW24038', 'layanan' => 'Cuci Kering',    'jumlah' => 72000, 'status' => 'Belum bayar'],
];

// Ringkasan
$totalPendapatan = 0;
$belumDibayar    = 0;
$jumlahLunas     = 0;
$jumlahBelum     = 0;
$perLayanan      = ['Cuci + Setrika' => 0, 'Cuci Kering' => 0, 'Setrika' => 0];

foreach ($pesanan as $p) {
    if ($p['status'] === 'Lunas') {
        $totalPendapatan += $p['jumlah'];
        $jumlahLunas++;
        $perLayanan[$p['layanan']] = ($perLayanan[$p['layanan']] ?? 0) + $p['jumlah'];
    } else {
        $belumDibayar += $p['jumlah'];
        $jumlahBelum++;
    }
}
$rataRata = $jumlahLunas > 0 ? intdiv($totalPendapatan, $jumlahLunas) : 0;

$kartu = [
    [
        'judul' => 'Total Pendapatan',
        'nilai' => rupiah($totalPendapatan),
        'ket'   => 'Pembayaran yang sudah diterima',
        'warna' => 'bg-[#dcefff] text-[#0085ff]',
        'icon'  => '<rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.5"/>',
    ],
    [
        'judul' => 'Belum Dibayar',
        'nilai' => rupiah($belumDibayar),
        'ket'   => $jumlahBelum . ' pesanan menunggu pembayaran',
        'warna' => 'bg-[#fdeec2] text-[#d97706]',
        'icon'  => '<path d="M19 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"/><path d="M21 8H5"/><path d="M16 12h5v4h-5a2 2 0 0 1 0-4Z"/>',
    ],
    [
        'judul' => 'Transaksi Lunas',
        'nilai' => (string) $jumlahLunas,
        'ket'   => 'Dari ' . count($pesanan) . ' pesanan dalam periode ini',
        'warna' => 'bg-[#d5f5e3] text-[#059669]',
        'icon'  => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="m9.5 14 2 2 3.5-4"/>',
    ],
    [
        'judul' => 'Rata-rata Transaksi',
        'nilai' => rupiah($rataRata),
        'ket'   => 'Per transaksi lunas',
        'warna' => 'bg-slate-100 text-slate-600',
        'icon'  => '<path d="M8 3 3 6l2 4 3-1v12h8V9l3 1 2-4-5-3a4 4 0 0 1-8 0Z"/>',
    ],
];

// Grafik pendapatan 7 hari terakhir (contoh)
$hariGrafik  = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$nilaiGrafik = [800000, 1100000, 900000, 1300000, 1000000, 1250000, 1450000];
$totalGrafik = array_sum($nilaiGrafik);
$teksTotalGrafik = 'Rp ' . number_format($totalGrafik / 1000000, 1, '.', '') . 'Jt';

$W = 700;
$H = 260;
$skala = 1600000;
$titik = [];
foreach ($nilaiGrafik as $i => $v) {
    $x = round(($i + 0.5) * $W / count($nilaiGrafik), 1);
    $y = round($H - 10 - ($v / $skala) * ($H - 40), 1);
    $titik[] = [$x, $y];
}
$garis = 'M' . implode(' L', array_map(fn($t) => $t[0] . ',' . $t[1], $titik));
$area  = $garis . ' L' . end($titik)[0] . ',' . $H . ' L' . $titik[0][0] . ',' . $H . ' Z';

// Pendapatan per layanan
$warnaLayanan = [
    'Cuci + Setrika' => 'bg-[#0085ff]',
    'Cuci Kering'    => 'bg-[#059669]',
    'Setrika'        => 'bg-[#d97706]',
];

$menu = [
    ['label' => 'Dasbor',        'href' => '../dashboard/index.php',    'aktif' => false, 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],
    ['label' => 'Pesanan',       'href' => '../pesanan/index.php',      'aktif' => false, 'icon' => '<path d="M8 3 3 6l2 4 3-1v12h8V9l3 1 2-4-5-3a4 4 0 0 1-8 0Z"/>'],
    ['label' => 'Pelanggan',     'href' => '../pelanggan/index.php',    'aktif' => false, 'icon' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.5A6.5 6.5 0 0 1 21.5 20"/>'],
    ['label' => 'Pembayaran',    'href' => '../pembayaran/index.php', 'aktif' => false, 'icon' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/>'],
    ['label' => 'Laporan',       'href' => 'index.php',                 'aktif' => true,  'icon' => '<path d="M3 3v18h18"/><path d="m7 15 4-5 3 3 5-6"/>'],
    ['label' => 'Log Aktivitas', 'href' => '../log-activity/index.php', 'aktif' => false, 'icon' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5"/><path d="M3.5 3.5v5h5"/><path d="M12 7.5V12l3 2"/>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>Laporan Pendapatan | Fast Wash</title>
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
                <a href="index" class="text-[#0085ff] hover:underline">Laporan Pendapatan</a>
                <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                <span class="text-slate-600" aria-current="page"><?= str_replace(' – ', ' - ', $teksPeriode) ?></span>
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
            <main class="px-5 pb-10 pt-8 lg:px-8">

                <!-- Judul halaman -->
                <section class="flex flex-wrap items-start justify-between gap-4" aria-labelledby="judul-laporan">
                    <div>
                        <p class="text-sm font-medium text-[#0085ff]">Keuangan Fast Wash</p>
                        <h1 id="judul-laporan" class="mt-1 text-4xl font-extrabold tracking-tight text-[#0085ff]">Laporan Pendapatan</h1>
                        <p class="mt-2 text-sm text-slate-500"><?= $teksPeriode ?></p>
                    </div>

                    <button type="button" class="flex items-center gap-2.5 rounded-xl bg-[#0085ff] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#0085ff]/25 transition hover:bg-[#0074e0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0085ff]/50 focus-visible:ring-offset-2">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 4v11"/>
                            <path d="m7.5 11 4.5 4.5 4.5-4.5"/>
                            <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17"/>
                        </svg>
                        Unduh PDF
                    </button>
                </section>

                <!-- Ringkasan -->
                <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pendapatan">
                    <?php foreach ($kartu as $k): ?>
                        <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-[15px] font-medium text-slate-700"><?= $k['judul'] ?></p>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full <?= $k['warna'] ?>">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <?= $k['icon'] ?>
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900"><?= $k['nilai'] ?></p>
                            <p class="mt-1 text-sm text-slate-500"><?= $k['ket'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- Filter periode -->
                <form id="form-periode" method="get" class="mt-8 flex flex-wrap items-end gap-4" aria-label="Filter periode laporan">
                    <div>
                        <label for="periode" class="block text-sm font-medium text-slate-500">Periode</label>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/></svg>
                            <select id="periode" name="periode" class="h-11 w-44 appearance-none rounded-xl border border-slate-200 bg-white pl-12 pr-10 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-none focus:ring-2 focus:ring-[#0085ff]/20">
                                <?php foreach (['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'kustom' => 'Kustom'] as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= $periodeDipilih === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>

                    <div>
                        <label for="mulai" class="block text-sm font-medium text-slate-500">Tanggal mulai</label>
                        <div class="relative mt-2">
                            <input id="mulai" name="mulai" type="date" value="<?= htmlspecialchars($mulai) ?>"
                                   class="h-11 w-44 rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-none focus:ring-2 focus:ring-[#0085ff]/20 [&::-webkit-calendar-picker-indicator]:opacity-0">
                            <svg class="pointer-events-none absolute right-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/></svg>
                        </div>
                    </div>

                    <div>
                        <label for="akhir" class="block text-sm font-medium text-slate-500">Tanggal akhir</label>
                        <div class="relative mt-2">
                            <input id="akhir" name="akhir" type="date" value="<?= htmlspecialchars($akhir) ?>"
                                   class="h-11 w-44 rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-none focus:ring-2 focus:ring-[#0085ff]/20 [&::-webkit-calendar-picker-indicator]:opacity-0">
                            <svg class="pointer-events-none absolute right-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/></svg>
                        </div>
                    </div>
                </form>

                <!-- Grafik + per layanan -->
                <div class="mt-8 grid items-start gap-5 xl:grid-cols-[2fr_1fr]">

                    <!-- Gambaran pendapatan -->
                    <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:p-8" aria-labelledby="judul-grafik">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 id="judul-grafik" class="text-xl font-bold tracking-tight text-[#0f2747]">Gambaran Pendapatan</h2>
                                <p class="mt-1 text-sm text-slate-400">7 hari terakhir</p>
                            </div>
                            <p class="text-xl font-extrabold tracking-tight text-[#0085ff]"><?= $teksTotalGrafik ?></p>
                        </div>

                        <div class="mt-6">
                            <svg class="h-64 w-full overflow-visible drop-shadow-[0_6px_6px_rgba(0,133,255,0.18)]" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" role="img" aria-label="Grafik pendapatan 7 hari terakhir, total <?= $teksTotalGrafik ?>">
                                <defs>
                                    <linearGradient id="isi-grafik" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#0085ff" stop-opacity="0.10"/>
                                        <stop offset="100%" stop-color="#0085ff" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="<?= $area ?>" fill="url(#isi-grafik)"/>
                                <path d="<?= $garis ?>" fill="none" stroke="#1a8cff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                            </svg>

                            <ul class="grid grid-cols-7 border-t border-slate-200/70 pt-5 text-center text-sm text-slate-400">
                                <?php foreach ($hariGrafik as $hari): ?>
                                    <li><?= $hari ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>

                    <!-- Pendapatan per layanan -->
                    <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:p-8" aria-labelledby="judul-layanan">
                        <h2 id="judul-layanan" class="text-xl font-bold tracking-tight text-slate-900">Pendapatan per Layanan</h2>
                        <p class="mt-1 text-sm text-slate-500">Berdasarkan transaksi lunas</p>

                        <ul class="mt-8 space-y-6">
                            <?php foreach ($perLayanan as $nama => $nilai): ?>
                                <?php $persen = $totalPendapatan > 0 ? (int) round($nilai / $totalPendapatan * 100) : 0; ?>
                                <li>
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="text-[15px] font-medium text-slate-800"><?= htmlspecialchars($nama) ?></p>
                                        <div class="text-right">
                                            <p class="text-[15px] font-bold text-slate-900"><?= rupiah($nilai) ?></p>
                                            <p class="text-sm text-slate-400"><?= $persen ?>%</p>
                                        </div>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Porsi <?= htmlspecialchars($nama) ?>">
                                        <div class="h-full rounded-full <?= $warnaLayanan[$nama] ?? 'bg-[#0085ff]' ?>" style="width: <?= $persen ?>%"></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Filter periode: isi tanggal otomatis lalu kirim form
        const form    = document.getElementById('form-periode');
        const periode = document.getElementById('periode');
        const mulai   = document.getElementById('mulai');
        const akhir   = document.getElementById('akhir');

        const dua = (n) => String(n).padStart(2, '0');
        const iso = (d) => d.getFullYear() + '-' + dua(d.getMonth() + 1) + '-' + dua(d.getDate());

        periode.addEventListener('change', () => {
            const t = new Date();
            let a = new Date(t), b = new Date(t);

            if (periode.value === 'mingguan') {
                a.setDate(t.getDate() - 6);
            } else if (periode.value === 'bulanan') {
                a = new Date(t.getFullYear(), t.getMonth(), 1);
                b = new Date(t.getFullYear(), t.getMonth() + 1, 0);
            } else if (periode.value === 'tahunan') {
                a = new Date(t.getFullYear(), 0, 1);
                b = new Date(t.getFullYear(), 11, 31);
            }

            if (periode.value !== 'kustom') {
                mulai.value = iso(a);
                akhir.value = iso(b);
            }
            form.submit();
        });

        [mulai, akhir].forEach((el) => el.addEventListener('change', () => {
            periode.value = 'kustom';
            form.submit();
        }));
    </script>
</body>
</html>
