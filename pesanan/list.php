<?php
$title = 'Daftar Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$listPath = 'pesanan';
$detailPath = 'pesanan/detail';
$newOrderPath = 'pesanan/tambah';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => $listPath],
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

<!-- Breadcrumb ditampilkan oleh header.php (komponen nav berchevron). -->

<!-- Filter & Pencarian (Satu Baris: Pencarian | Status | Bayar | Aksi) -->
<form method="get" action="<?php echo $base . $listPath; ?>" class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative flex-1 min-w-0 max-w-2xl">
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
               class="w-full pl-10 pr-8 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] placeholder:text-slate-400 text-slate-700">
    </div>

    <div class="relative">
        <select name="status" onchange="this.form.submit()" style="width: 9rem; padding-left: 1rem;" class="appearance-none pl-6 pr-10 whitespace-nowrap py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
            <option value="">Status Cucian</option>
            <option value="diterima" <?php echo $filterStatus === 'diterima' ? 'selected' : ''; ?>>Diterima</option>
            <option value="diproses" <?php echo $filterStatus === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
            <option value="selesai" <?php echo $filterStatus === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
            <option value="diambil" <?php echo $filterStatus === 'diambil' ? 'selected' : ''; ?>>Diambil</option>
        </select>
    </div>

    <div class="relative">
        <select name="bayar" onchange="this.form.submit()" style="width: 11rem; padding-left: 1rem;" class="appearance-none pl-6 pr-10 whitespace-nowrap py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
            <option value="">Status Pembayaran</option>
            <option value="belum_lunas" <?php echo $filterBayar === 'belum_lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
            <option value="lunas" <?php echo $filterBayar === 'lunas' ? 'selected' : ''; ?>>Lunas</option>
        </select>
    </div>

    <?php if ($cari !== '' || $filterStatus !== '' || $filterBayar !== ''): ?>
        <a href="<?php echo $base . $listPath; ?>" class="text-xs font-semibold text-rose-500 hover:underline px-2 py-2 whitespace-nowrap">Reset</a>
    <?php endif; ?>

    <a href="<?php echo $base . $newOrderPath; ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0085ff] hover:bg-[#0070d8] text-white font-semibold text-sm rounded-2xl shadow-sm shadow-[#0085ff]/20 transition-all whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        <span>Pesanan Baru</span>
    </a>
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
                                <a href="<?php echo $base . $detailPath; ?>?id=<?php echo (int) $row['id_pesanan']; ?>" class="hover:underline">
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
                                        <a href="<?php echo $base . $detailPath; ?>?id=<?php echo (int) $row['id_pesanan']; ?>" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Status</a>
                                    <?php if ($isLunas): ?>
                                        <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $row['id_pesanan']; ?>" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Lihat Nota</a>
                                    <?php else: ?>
                                        <button type="button" onclick="bukaModalBayar(<?php echo (int) $row['id_pesanan']; ?>, '<?php echo e($row['kode_pesanan']); ?>', '<?php echo e($row['nama_pelanggan']); ?>', <?php echo (float) $row['total_harga']; ?>)" class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">Bayar</button>
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
                        <a href="<?php echo $base . $listPath; ?>?page=<?php echo $i; ?>&cari=<?php echo urlencode($cari); ?>&status=<?php echo urlencode($filterStatus); ?>&bayar=<?php echo urlencode($filterBayar); ?>" class="px-3 py-1.5 border border-slate-200 text-slate-600 text-xs font-normal rounded-lg hover:bg-slate-50 transition-colors">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="modalBayar" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl border border-slate-100 p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="text-base font-bold text-slate-800">Konfirmasi Pembayaran</h3>
                <button type="button" onclick="tutupModalBayar()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>
            <form action="<?php echo $base; ?>transaksi/bayar" method="post" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id_pesanan" id="modalIdPesanan" value="">
                <div class="p-4 bg-slate-50 rounded-2xl space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Kode Pesanan:</span>
                        <span class="font-bold text-[#0085ff]" id="modalKodePesanan">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Pelanggan:</span>
                        <span class="font-semibold text-slate-800" id="modalNamaPelanggan">-</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-slate-200">
                        <span class="text-slate-600 font-medium">Total Tagihan:</span>
                        <span class="font-bold text-[#0085ff] text-sm" id="modalTotalHarga">Rp 0</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50/50 transition-all text-xs">
                            <input type="radio" name="metode" value="tunai" checked class="hidden">
                            <span class="font-semibold text-slate-800">Tunai</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50/50 transition-all text-xs">
                            <input type="radio" name="metode" value="qris" class="hidden">
                            <span class="font-semibold text-slate-800">QRIS</span>
                        </label>
                        <label class="flex flex-col items-center justify-center p-3 border-2 border-slate-200 rounded-2xl cursor-pointer hover:border-[#0085ff] has-checked:border-[#0085ff] has-checked:bg-blue-50/50 transition-all text-xs">
                            <input type="radio" name="metode" value="transfer" class="hidden">
                            <span class="font-semibold text-slate-800">Transfer</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 pt-3">
                    <button type="button" onclick="tutupModalBayar()" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-[#0085ff] hover:bg-[#0070d8] rounded-xl shadow-xs transition-colors">Konfirmasi Lunas</button>
                </div>
            </form>
        </div>
    </div>
<script>
    function bukaModalBayar(id, kode, nama, total) {
        document.getElementById('modalIdPesanan').value = id;
        document.getElementById('modalKodePesanan').textContent = kode;
        document.getElementById('modalNamaPelanggan').textContent = nama;
        document.getElementById('modalTotalHarga').textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
        document.getElementById('modalBayar').classList.remove('hidden');
    }

    function tutupModalBayar() {
        document.getElementById('modalBayar').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
