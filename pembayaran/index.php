<?php
// Data contoh (nanti ganti dengan query database)
$pembayaran = [
    ['no' => '#FW24042', 'pelanggan' => 'Nadya Alya',     'metode' => 'Tunai',    'tanggal' => '18 Okt', 'jumlah' => 45000, 'status' => 'Belum bayar'],
    ['no' => '#FW24041', 'pelanggan' => 'Sarah Amanda',   'metode' => 'QRIS',     'tanggal' => '18 Okt', 'jumlah' => 24000, 'status' => 'Lunas'],
    ['no' => '#FW24040', 'pelanggan' => 'Alyssa Naura',   'metode' => 'Transfer', 'tanggal' => '17 Okt', 'jumlah' => 40000, 'status' => 'Lunas'],
    ['no' => '#FW24039', 'pelanggan' => 'Rizky Pratama',  'metode' => 'Tunai',    'tanggal' => '17 Okt', 'jumlah' => 70000, 'status' => 'Belum bayar'],
    ['no' => '#FW24038', 'pelanggan' => 'Dimas Aditya',   'metode' => 'Tunai',    'tanggal' => '17 Okt', 'jumlah' => 72000, 'status' => 'Belum bayar'],
];

$totalData   = 14;
$perPage     = 5;
$totalHalaman = (int) ceil($totalData / $perPage);
$halaman     = isset($_GET['page']) ? max(1, min($totalHalaman, (int) $_GET['page'])) : 1;

// Ringkasan
$diterima = 0;
$belumDibayar = 0;
$jumlahTransaksi = 0;
foreach ($pembayaran as $p) {
    if ($p['status'] === 'Lunas') {
        $diterima += $p['jumlah'];
        $jumlahTransaksi++;
    } else {
        $belumDibayar += $p['jumlah'];
    }
}

function rupiah(int $angka, bool $spasi = false): string
{
    return 'Rp' . ($spasi ? ' ' : '') . number_format($angka, 0, ',', '.');
}

$menu = [
    ['label' => 'Dasbor',        'href' => '../dashboard/index.php', 'aktif' => false, 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],
    ['label' => 'Pesanan',       'href' => '../pesanan/index.php',   'aktif' => false, 'icon' => '<path d="M8 3 3 6l2 4 3-1v12h8V9l3 1 2-4-5-3a4 4 0 0 1-8 0Z"/>'],
    ['label' => 'Pelanggan',     'href' => '../pelanggan/index.php', 'aktif' => false, 'icon' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.5A6.5 6.5 0 0 1 21.5 20"/>'],
    ['label' => 'Pembayaran',    'href' => 'pembayaran.php',         'aktif' => true,  'icon' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19"/>'],
    ['label' => 'Absensi',       'href' => '../absensi/index.php',   'aktif' => false, 'icon' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/><path d="m9.5 15.5 2 2 3.5-4"/>'],
    ['label' => 'Log Aktivitas', 'href' => '../log-activity/index.php', 'aktif' => false, 'icon' => '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5"/><path d="M3.5 3.5v5h5"/><path d="M12 7.5V12l3 2"/>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>Pembayaran | Fast Wash</title>
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
                <a href="../pembayaran/index.php" class="text-[#0085ff] hover:underline">Pembayaran</a>
                <svg class="size-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                <span class="text-slate-600" aria-current="page">Daftar Pembayaran</span>
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

            <main class="px-8 pb-10 pt-8">

                <!-- Ringkasan -->
                <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan pembayaran">
                    <div class="rounded-3xl bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <p class="text-sm font-medium text-slate-500">Diterima hari ini</p>
                        <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900"><?= rupiah($diterima, true) ?></p>
                    </div>
                    <div class="rounded-3xl bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                        <p class="text-sm font-medium text-slate-500">Belum dibayar</p>
                        <p class="mt-3 text-4xl font-extrabold tracking-tight text-[#d97706]"><?= rupiah($belumDibayar, true) ?></p>
                    </div>
                    <div class="rounded-3xl bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:col-span-2 xl:col-span-1">
                        <p class="text-sm font-medium text-slate-500">Transaksi</p>
                        <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900"><?= $jumlahTransaksi ?></p>
                    </div>
                </section>

                <!-- Info -->
                <div class="mt-7 flex items-center gap-4 rounded-full border border-[#d3e4f8] bg-[#e3eefc] px-6 py-4 text-[#0a6fd6]" role="note">
                    <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="4.5" y="10.5" width="15" height="10.5" rx="2.5"/>
                        <path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>
                    </svg>
                    <p class="text-sm font-medium">Riwayat transaksi bersifat permanen dan tidak dapat dihapus. Koreksi dicatat sebagai transaksi baru.</p>
                </div>

                <!-- Tabel pembayaran -->
                <section class="mt-7 overflow-hidden rounded-3xl bg-white shadow-[0_6px_20px_rgba(15,23,42,0.05)]" aria-label="Daftar pembayaran">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-175 text-left text-sm">
                            <thead class="bg-[#0085ff] text-white">
                                <tr>
                                    <th scope="col" class="py-5 pl-9 pr-4 font-medium">No. Pesanan</th>
                                    <th scope="col" class="px-4 py-5 font-medium">Pelanggan</th>
                                    <th scope="col" class="px-4 py-5 font-medium">Metode</th>
                                    <th scope="col" class="px-4 py-5 font-medium">Tanggal</th>
                                    <th scope="col" class="px-4 py-5 font-medium">Jumlah</th>
                                    <th scope="col" class="px-4 py-5 pr-9 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($pembayaran as $p): ?>
                                    <?php
                                    $lunas = $p['status'] === 'Lunas';
                                    $badge = $lunas
                                        ? 'bg-[#dcfce7] text-[#15803d]'
                                        : 'bg-[#fef3c7] text-[#b45309]';
                                    ?>
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="py-6 pl-9 pr-4 text-slate-800"><?= htmlspecialchars($p['no']) ?></td>
                                        <td class="px-4 py-6 text-slate-700"><?= htmlspecialchars($p['pelanggan']) ?></td>
                                        <td class="px-4 py-6 text-slate-500"><?= htmlspecialchars($p['metode']) ?></td>
                                        <td class="px-4 py-6 text-slate-500"><?= htmlspecialchars($p['tanggal']) ?></td>
                                        <td class="px-4 py-6 text-slate-800"><?= rupiah($p['jumlah']) ?></td>
                                        <td class="px-4 py-6 pr-9">
                                            <span class="inline-block rounded-full px-3.5 py-1.5 text-xs font-medium <?= $badge ?>">
                                                <?= htmlspecialchars($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginasi -->
                    <div class="flex flex-col gap-4 border-t border-slate-100 px-9 py-6 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-500">
                            Menampilkan <span class="font-bold text-slate-700"><?= count($pembayaran) ?></span>
                            dari <span class="font-bold text-slate-700"><?= $totalData ?></span> pembayaran
                        </p>

                        <nav aria-label="Paginasi" class="flex items-center gap-2">
                            <?php if ($halaman > 1): ?>
                                <a href="?page=<?= $halaman - 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Sebelumnya</a>
                            <?php else: ?>
                                <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Sebelumnya</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                                <?php if ($i === $halaman): ?>
                                    <span aria-current="page" class="flex size-10 items-center justify-center rounded-xl bg-[#0085ff] text-sm font-semibold text-white">
                                        <?= $i ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?>" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($halaman < $totalHalaman): ?>
                                <a href="?page=<?= $halaman + 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50">Berikutnya</a>
                            <?php else: ?>
                                <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Berikutnya</span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>