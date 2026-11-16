<?php
/**
 * Fast Wash - Laporan Pendapatan (Pemilik)
 * Sesuai desain Figma (Fast Wash - Laporan (Pemilik).png)
 */
$title = 'Laporan Pendapatan';
$route = $route ?? 'laporan';
$userRole = $_SESSION['role'] ?? 'pemilik';

date_default_timezone_set('Asia/Jakarta');

$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

if (!function_exists('rupiah')) {
    function rupiah(int $angka, bool $spasi = true): string
    {
        return 'Rp' . ($spasi ? ' ' : '') . number_format($angka, 0, ',', '.');
    }
}

if (!function_exists('tanggalPanjang')) {
    function tanggalPanjang(string $ymd, array $bulan): string
    {
        $t = DateTime::createFromFormat('Y-m-d', $ymd) ?: new DateTime();
        return $t->format('j') . ' ' . $bulan[(int) $t->format('n')] . ' ' . $t->format('Y');
    }
}

// Filter periode (default sesuai desain mockup)
$periodeDipilih = $_GET['periode'] ?? 'bulanan';
$mulai = $_GET['mulai'] ?? '2026-10-01';
$akhir = $_GET['akhir'] ?? '2026-10-31';
$teksPeriode = tanggalPanjang($mulai, $bulan) . ' – ' . tanggalPanjang($akhir, $bulan);

// Breadcrumb Top Header Bar
$breadcrumb = [
    ['label' => 'Laporan Pendapatan', 'url' => 'laporan'],
    ['label' => tanggalPanjang($mulai, $bulan) . ' - ' . tanggalPanjang($akhir, $bulan), 'url' => ''],
];

$extra_css = 'assets/css/pembayaran-dan-laporan.css';

// Data pesanan dalam periode (sesuai spesifikasi & mockup)
$pesanan = [
    ['no' => '#FW24042', 'layanan' => 'Cuci + Setrika', 'jumlah' => 45000, 'status' => 'Belum bayar'],
    ['no' => '#FW24041', 'layanan' => 'Cuci Kering',    'jumlah' => 24000, 'status' => 'Lunas'],
    ['no' => '#FW24040', 'layanan' => 'Setrika',        'jumlah' => 40000, 'status' => 'Lunas'],
    ['no' => '#FW24039', 'layanan' => 'Cuci + Setrika', 'jumlah' => 70000, 'status' => 'Belum bayar'],
    ['no' => '#FW24038', 'layanan' => 'Cuci Kering',    'jumlah' => 72000, 'status' => 'Belum bayar'],
];

// Ringkasan metrik
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
        'warna' => 'bg-[#f1f5f9] text-slate-500',
        'icon'  => '<path d="M16 3l4 3-2 5-3-1v10H9V10L6 11 4 6l4-3c1 1.5 3 2 4 2s3-.5 4-2z"/>',
    ],
];

// Grafik pendapatan 7 hari terakhir
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
    $y = round($H - 20 - ($v / $skala) * ($H - 60), 1);
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

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-7 pb-10">

    <!-- Judul halaman & Tombol Aksi -->
    <section class="flex flex-wrap items-start justify-between gap-4 pt-1" aria-labelledby="judul-laporan">
        <div>
            <p class="text-sm font-semibold text-[#0085ff]">Keuangan Fast Wash</p>
            <h1 id="judul-laporan" class="mt-1 text-3xl sm:text-4xl font-extrabold tracking-tight text-[#0085ff]">Laporan Pendapatan</h1>
            <p class="mt-2 text-sm text-slate-500 font-medium"><?= e($teksPeriode) ?></p>
        </div>

        <button type="button"
                onclick="window.print()"
                class="flex items-center gap-2.5 rounded-2xl bg-[#0085ff] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#0085ff]/25 transition hover:bg-[#0074e0] focus:outline-hidden">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 4v11"/>
                <path d="m7.5 11 4.5 4.5 4.5-4.5"/>
                <path d="M4 17v1.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V17"/>
            </svg>
            Unduh PDF
        </button>
    </section>

    <!-- Ringkasan Kartu KPI -->
    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pendapatan">
        <?php foreach ($kartu as $k): ?>
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)]">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[15px] font-medium text-slate-600"><?= e($k['judul']) ?></p>
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full <?= $k['warna'] ?>">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <?= $k['icon'] ?>
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900"><?= e($k['nilai']) ?></p>
                <p class="mt-1 text-sm text-slate-400"><?= e($k['ket']) ?></p>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Filter Periode -->
    <form id="form-periode" method="get" class="flex flex-wrap items-end gap-4" aria-label="Filter periode laporan">
        <div>
            <label for="periode" class="block text-sm font-medium text-slate-500">Periode</label>
            <div class="relative mt-2">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                    <path d="M8 2.5v4M16 2.5v4M3 10h18"/>
                </svg>
                <select id="periode" name="periode" class="h-11 w-44 appearance-none rounded-xl border border-slate-200 bg-white pl-12 pr-10 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20">
                    <?php foreach (['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'kustom' => 'Kustom'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $periodeDipilih === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
                <svg class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
        </div>

        <div>
            <label for="mulai" class="block text-sm font-medium text-slate-500">Tanggal mulai</label>
            <div class="relative mt-2">
                <input id="mulai" name="mulai" type="date" value="<?= e($mulai) ?>"
                       class="h-11 w-44 rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 [&::-webkit-calendar-picker-indicator]:opacity-0">
                <svg class="pointer-events-none absolute right-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                    <path d="M8 2.5v4M16 2.5v4M3 10h18"/>
                </svg>
            </div>
        </div>

        <div>
            <label for="akhir" class="block text-sm font-medium text-slate-500">Tanggal akhir</label>
            <div class="relative mt-2">
                <input id="akhir" name="akhir" type="date" value="<?= e($akhir) ?>"
                       class="h-11 w-44 rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-800 focus:border-[#0085ff] focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 [&::-webkit-calendar-picker-indicator]:opacity-0">
                <svg class="pointer-events-none absolute right-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/>
                    <path d="M8 2.5v4M16 2.5v4M3 10h18"/>
                </svg>
            </div>
        </div>
    </form>

    <!-- Grafik + Pendapatan Per Layanan -->
    <div class="grid items-start gap-6 xl:grid-cols-[2fr_1fr]">

        <!-- Gambaran Pendapatan -->
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:p-8" aria-labelledby="judul-grafik">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="judul-grafik" class="text-xl font-bold tracking-tight text-[#0f2747]">Gambaran Pendapatan</h2>
                    <p class="mt-1 text-sm text-slate-400">7 hari terakhir</p>
                </div>
                <p class="text-xl font-extrabold tracking-tight text-[#0085ff]"><?= e($teksTotalGrafik) ?></p>
            </div>

            <div class="mt-6">
                <svg class="h-64 w-full overflow-visible drop-shadow-[0_6px_6px_rgba(0,133,255,0.18)]" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" role="img" aria-label="Grafik pendapatan 7 hari terakhir, total <?= e($teksTotalGrafik) ?>">
                    <defs>
                        <linearGradient id="isi-grafik" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0085ff" stop-opacity="0.12"/>
                            <stop offset="100%" stop-color="#0085ff" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="<?= $area ?>" fill="url(#isi-grafik)"/>
                    <path d="<?= $garis ?>" fill="none" stroke="#1a8cff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                </svg>

                <ul class="grid grid-cols-7 border-t border-slate-100 pt-5 text-center text-sm font-medium text-slate-400">
                    <?php foreach ($hariGrafik as $hari): ?>
                        <li><?= e($hari) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- Pendapatan per Layanan -->
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-[0_6px_20px_rgba(15,23,42,0.05)] sm:p-8" aria-labelledby="judul-layanan">
            <h2 id="judul-layanan" class="text-xl font-bold tracking-tight text-slate-900">Pendapatan per Layanan</h2>
            <p class="mt-1 text-sm text-slate-400">Berdasarkan transaksi lunas</p>

            <ul class="mt-8 space-y-6">
                <?php foreach ($perLayanan as $nama => $nilai): ?>
                    <?php $persen = $totalPendapatan > 0 ? (int) round($nilai / $totalPendapatan * 100) : 0; ?>
                    <li>
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[15px] font-semibold text-slate-800"><?= e($nama) ?></p>
                            <div class="text-right">
                                <p class="text-[15px] font-bold text-slate-900"><?= rupiah($nilai) ?></p>
                                <p class="text-xs font-semibold text-slate-400"><?= $persen ?>%</p>
                            </div>
                        </div>
                        <div class="mt-2.5 h-2.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Porsi <?= e($nama) ?>">
                            <div class="h-full rounded-full <?= $warnaLayanan[$nama] ?? 'bg-[#0085ff]' ?>" style="width: <?= $persen ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

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

    if (periode && form && mulai && akhir) {
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
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
