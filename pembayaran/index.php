<?php
/**
 * Fast Wash - Daftar Pembayaran (Pemilik)
 * Sesuai desain Figma (Fast Wash - Pembayaran (Pemilik).png)
 */
$title = 'Daftar Pembayaran';
$route = $route ?? 'pembayaran';
$userRole = $_SESSION['role'] ?? 'pemilik';

// Data contoh pembayaran (sesuai spesifikasi & mockup Figma)
$pembayaran = [
    ['no' => '#FW24042', 'pelanggan' => 'Nadya Alya',     'metode' => 'Tunai',    'tanggal' => '18 Okt', 'jumlah' => 45000, 'status' => 'Belum bayar'],
    ['no' => '#FW24041', 'pelanggan' => 'Sarah Amanda',   'metode' => 'QRIS',     'tanggal' => '18 Okt', 'jumlah' => 24000, 'status' => 'Lunas'],
    ['no' => '#FW24040', 'pelanggan' => 'Alyssa Naura',   'metode' => 'Transfer', 'tanggal' => '17 Okt', 'jumlah' => 40000, 'status' => 'Lunas'],
    ['no' => '#FW24039', 'pelanggan' => 'Rizky Pratama',  'metode' => 'Tunai',    'tanggal' => '17 Okt', 'jumlah' => 70000, 'status' => 'Belum bayar'],
    ['no' => '#FW24038', 'pelanggan' => 'Dimas Aditya',   'metode' => 'Tunai',    'tanggal' => '17 Okt', 'jumlah' => 72000, 'status' => 'Belum bayar'],
];

$totalData    = 14;
$perPage      = 5;
$totalHalaman = (int) ceil($totalData / $perPage);
$halaman      = isset($_GET['page']) ? max(1, min($totalHalaman, (int) $_GET['page'])) : 1;

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

if (!function_exists('rupiah')) {
    function rupiah(int $angka, bool $spasi = false): string
    {
        return 'Rp' . ($spasi ? ' ' : '') . number_format($angka, 0, ',', '.');
    }
}

// Breadcrumb Top Header Bar
$breadcrumb = [
    ['label' => 'Pelanggan', 'url' => 'pelanggan/list'],
    ['label' => 'Daftar Pembayaran', 'url' => ''],
];

$extra_css = 'assets/css/pembayaran-dan-laporan.css';

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-7 pb-10">

    <!-- Ringkasan Kartu KPI -->
    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 pt-1" aria-label="Ringkasan pembayaran">
        <div class="rounded-3xl border border-slate-100 bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
            <p class="text-sm font-medium text-slate-500">Diterima hari ini</p>
            <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900"><?= rupiah($diterima, true) ?></p>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
            <p class="text-sm font-medium text-slate-500">Belum dibayar</p>
            <p class="mt-3 text-4xl font-extrabold tracking-tight text-[#d97706]"><?= rupiah($belumDibayar, true) ?></p>
        </div>
        <div class="rounded-3xl border border-slate-100 bg-white px-7 py-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:col-span-2 xl:col-span-1">
            <p class="text-sm font-medium text-slate-500">Transaksi</p>
            <p class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900"><?= $jumlahTransaksi ?></p>
        </div>
    </section>

    <!-- Info Banner Audit Trail -->
    <div class="flex items-center gap-4 rounded-full border border-[#d3e4f8] bg-[#e3eefc] px-6 py-4 text-[#0a6fd6]" role="note">
        <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="4.5" y="10.5" width="15" height="10.5" rx="2.5"/>
            <path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>
        </svg>
        <p class="text-sm font-medium">Riwayat transaksi bersifat permanen dan tidak dapat dihapus. Koreksi dicatat sebagai transaksi baru.</p>
    </div>

    <!-- Tabel Pembayaran -->
    <section class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_6px_20px_rgba(15,23,42,0.05)]" aria-label="Daftar pembayaran">
        <div class="overflow-x-auto">
            <table class="w-full min-w-175 text-left text-sm">
                <thead class="bg-[#0085ff] text-white">
                    <tr>
                        <th scope="col" class="py-5 pl-9 pr-4 font-semibold">No. Pesanan</th>
                        <th scope="col" class="px-4 py-5 font-semibold">Pelanggan</th>
                        <th scope="col" class="px-4 py-5 font-semibold">Metode</th>
                        <th scope="col" class="px-4 py-5 font-semibold">Tanggal</th>
                        <th scope="col" class="px-4 py-5 font-semibold">Jumlah</th>
                        <th scope="col" class="px-4 py-5 pr-9 font-semibold">Status</th>
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
                            <td class="py-6 pl-9 pr-4 font-medium text-slate-800"><?= e($p['no']) ?></td>
                            <td class="px-4 py-6 font-medium text-slate-700"><?= e($p['pelanggan']) ?></td>
                            <td class="px-4 py-6 text-slate-500"><?= e($p['metode']) ?></td>
                            <td class="px-4 py-6 text-slate-500"><?= e($p['tanggal']) ?></td>
                            <td class="px-4 py-6 font-medium text-slate-800"><?= rupiah($p['jumlah']) ?></td>
                            <td class="px-4 py-6 pr-9">
                                <span class="inline-block rounded-full px-3.5 py-1.5 text-xs font-semibold <?= $badge ?>">
                                    <?= e($p['status']) ?>
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
                    <a href="?page=<?= $halaman - 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Sebelumnya</a>
                <?php else: ?>
                    <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Sebelumnya</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <?php if ($i === $halaman): ?>
                        <span aria-current="page" class="flex size-10 items-center justify-center rounded-xl bg-[#0085ff] text-sm font-semibold text-white shadow-xs">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>" class="flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($halaman < $totalHalaman): ?>
                    <a href="?page=<?= $halaman + 1 ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Berikutnya</a>
                <?php else: ?>
                    <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-400">Berikutnya</span>
                <?php endif; ?>
            </nav>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
