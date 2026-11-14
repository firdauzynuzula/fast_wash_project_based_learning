<?php
$title = 'Daftar Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Daftar Pesanan', 'url' => ''],
];

// Parameter Filter & Pencarian
$cari         = trim($_GET['cari'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterBayar  = trim($_GET['bayar'] ?? '');

// Pagination sederhana
$halamanAktif = max(1, (int) ($_GET['page'] ?? 1));
$limit        = 10;
$offset       = ($halamanAktif - 1) * $limit;

// Bangun query filter
$sqlWhere = " WHERE 1=1";
$params = [];

if ($cari !== '') {
    $sqlWhere .= " AND (p.kode_pesanan ILIKE :cari OR pl.nama ILIKE :cari OR pl.no_telepon ILIKE :cari)";
    $params['cari'] = '%' . $cari . '%';
}

if ($filterStatus !== '' && in_array($filterStatus, ['diterima', 'diproses', 'selesai', 'diambil'], true)) {
    $sqlWhere .= " AND p.status = :status";
    $params['status'] = $filterStatus;
}

if ($filterBayar !== '' && in_array($filterBayar, ['lunas', 'belum_lunas'], true)) {
    $sqlWhere .= " AND p.status_bayar = :bayar";
    $params['bayar'] = $filterBayar;
}

// Hitung total data
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan" . $sqlWhere);
$stmtCount->execute($params);
$totalPesanan = (int) $stmtCount->fetchColumn();
$totalHalaman = max(1, (int) ceil($totalPesanan / $limit));

// Query data pesanan
$sql = "SELECT p.id_pesanan,
               p.kode_pesanan,
               pl.nama AS nama_pelanggan,
               pl.no_telepon,
               p.status AS progres_cucian,
               p.status_bayar AS status_pembayaran,
               p.total_harga,
               p.tanggal_pesan,
               p.estimasi_selesai
          FROM pesanan p
          JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
          {$sqlWhere}
         ORDER BY p.tanggal_pesan DESC
         LIMIT {$limit} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarPesanan = $stmt->fetchAll();

// Flash Message
$flashSukses = $_SESSION['flash_sukses'] ?? null;
$flashError  = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Daftar Pesanan</h1>
        <p class="text-sm text-slate-500 mt-0.5">Kelola semua pesanan laundry Anda</p>
    </div>
    <div>
        <a href="<?php echo $base; ?>pesanan/tambah" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0085ff] hover:bg-[#0070d8] text-white font-semibold text-sm rounded-2xl shadow-sm shadow-[#0085ff]/20 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            <span>+ Pesanan Baru</span>
        </a>
    </div>
</div>

<!-- Filter & Pencarian (Sesuai Desain Figma: 1 Baris Elegan) -->
<form method="get" action="<?php echo $base; ?>pesanan" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 mb-6">
    <div class="relative flex-1 max-w-xl">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
            </svg>
        </div>
        <input type="text"
               name="cari"
               value="<?php echo e($cari); ?>"
               placeholder="Cari Nama Pelanggan / Kode Pesanan"
               class="w-full pl-10 pr-10 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] placeholder:text-slate-400 text-slate-700">
        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
        <div class="relative">
            <select name="status" onchange="this.form.submit()" class="appearance-none w-full sm:w-auto pl-4 pr-9 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
                <option value="">Status Cucian</option>
                <option value="diterima" <?php echo $filterStatus === 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                <option value="diproses" <?php echo $filterStatus === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                <option value="selesai" <?php echo $filterStatus === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                <option value="diambil" <?php echo $filterStatus === 'diambil' ? 'selected' : ''; ?>>Diambil</option>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
        <div class="relative">
            <select name="bayar" onchange="this.form.submit()" class="appearance-none w-full sm:w-auto pl-4 pr-9 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
                <option value="">Status Pembayaran</option>
                <option value="belum_lunas" <?php echo $filterBayar === 'belum_lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
                <option value="lunas" <?php echo $filterBayar === 'lunas' ? 'selected' : ''; ?>>Lunas</option>
            </select>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>
        <?php if ($cari !== '' || $filterStatus !== '' || $filterBayar !== ''): ?>
            <a href="<?php echo $base; ?>pesanan" class="text-xs font-semibold text-rose-500 hover:underline px-2 py-2">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Tabel Daftar Pesanan Sesuai Tampilan Figma (Header Biru #0085ff) -->
<div class="bg-white rounded-3xl shadow-xs border border-slate-100 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 border-collapse">
            <thead class="bg-[#0085ff] text-white text-xs font-semibold">
                <tr>
                    <th scope="col" class="py-4 px-6 font-semibold">Kode</th>
                    <th scope="col" class="py-4 px-6 font-semibold">Nama Pelanggan</th>
                    <th scope="col" class="py-4 px-6 font-semibold text-center">Progres Cucian</th>
                    <th scope="col" class="py-4 px-6 font-semibold text-center">Status Pembayaran</th>
                    <th scope="col" class="py-4 px-6 font-semibold text-center">Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php if (empty($daftarPesanan)): ?>
                    <tr>
                        <td colspan="5" class="py-16 text-center text-slate-400">
                            <p class="font-medium text-slate-600 text-base">Tidak ada pesanan ditemukan</p>
                            <p class="text-xs text-slate-400 mt-1">Belum ada pesanan yang sesuai dengan filter pencarian.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarPesanan as $row): ?>
                        <?php
                        $st = strtolower($row['progres_cucian']);
                        $isLunas = ($row['status_pembayaran'] === 'lunas');

                        $badgeProgres = match ($st) {
                            'diterima' => ['label' => 'Diterima', 'class' => 'border border-blue-200 text-[#0085ff] bg-blue-50/50'],
                            'diproses' => ['label' => 'Diproses', 'class' => 'border border-blue-300 text-[#0085ff] bg-blue-50'],
                            'selesai' => ['label' => 'Selesai', 'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50'],
                            'diambil' => ['label' => 'Diambil', 'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50'],
                            default => ['label' => ucfirst($st), 'class' => 'border border-slate-200 text-slate-600 bg-slate-50'],
                        };

                        $badgeBayar = $isLunas
                            ? ['label' => 'Lunas', 'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50']
                            : ['label' => 'Belum Lunas', 'class' => 'border border-amber-300 text-amber-600 bg-amber-50'];
                        ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-[#0085ff]">
                                <a href="<?php echo $base; ?>pesanan/detail?id=<?php echo (int) $row['id_pesanan']; ?>" class="hover:underline">
                                    <?php echo e($row['kode_pesanan']); ?>
                                </a>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-slate-800">
                                <?php echo e($row['nama_pelanggan']); ?>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-normal <?php echo $badgeProgres['class']; ?>">
                                    <?php echo $badgeProgres['label']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-normal <?php echo $badgeBayar['class']; ?>">
                                    <?php echo $badgeBayar['label']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <a href="<?php echo $base; ?>pesanan/detail?id=<?php echo (int) $row['id_pesanan']; ?>" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Status</a>

                                    <?php if (!$isLunas): ?>
                                        <form method="post" action="<?php echo $base; ?>transaksi/bayar" class="inline">
                                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="id_pesanan" value="<?php echo (int) $row['id_pesanan']; ?>">
                                            <input type="hidden" name="redirect" value="<?php echo $base; ?>pesanan/detail?id=<?php echo (int) $row['id_pesanan']; ?>">
                                            <button type="submit" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Bayar</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($isLunas): ?>
                                        <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $row['id_pesanan']; ?>" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Lihat Nota</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPesanan > 0): ?>
        <div class="flex items-center justify-between px-6 py-3 border-t border-slate-100 text-xs text-slate-500">
            <span>Menampilkan <?php echo min($limit, $totalPesanan); ?> dari <?php echo $totalPesanan; ?> pesanan</span>
            <div>
                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <?php if ($i === $halamanAktif): ?>
                        <span class="px-3 py-1.5 bg-[#0085ff] text-white text-xs font-semibold rounded-lg"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $base; ?>pesanan?page=<?php echo $i; ?>&cari=<?php echo urlencode($cari); ?>&status=<?php echo urlencode($filterStatus); ?>&bayar=<?php echo urlencode($filterBayar); ?>" class="px-3 py-1.5 border border-slate-200 text-slate-600 text-xs font-normal rounded-lg hover:bg-slate-50 transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
