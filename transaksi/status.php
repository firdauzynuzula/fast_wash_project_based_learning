<?php
$title = 'Daftar Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'transaksi/status'],
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

<!-- Alert Notifikasi Flash -->
<?php if ($flashSukses): ?>
    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo e($flashSukses); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span><?php echo e($flashError); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<!-- Bar Filter & Pencarian (Sesuai Desain Figma: 1 Baris Elegan) -->
<form method="get" action="<?php echo $base; ?>transaksi/status" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 mb-6">
    <!-- Input Pencarian -->
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

    <!-- Dropdown Status Cucian, Status Pembayaran & Tombol + Pesanan Baru -->
    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
        <!-- Status Cucian -->
        <div class="relative">
            <select name="status"
                    onchange="this.form.submit()"
                    class="appearance-none w-full sm:w-auto pl-4 pr-9 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
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

        <!-- Status Pembayaran -->
        <div class="relative">
            <select name="bayar"
                    onchange="this.form.submit()"
                    class="appearance-none w-full sm:w-auto pl-4 pr-9 py-2.5 text-sm bg-white border border-slate-200 rounded-2xl shadow-xs focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/20 focus:border-[#0085ff] text-slate-700 cursor-pointer font-normal">
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
            <a href="<?php echo $base; ?>transaksi/status" class="text-xs font-semibold text-rose-500 hover:underline px-2 py-2">
                Reset
            </a>
        <?php endif; ?>

        <!-- Tombol + Pesanan Baru -->
        <a href="<?php echo $base; ?>transaksi/tambah"
           class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-[#0085ff] hover:bg-[#0070d8] text-white font-semibold text-sm rounded-2xl shadow-sm shadow-[#0085ff]/20 transition-all shrink-0">
            <span>+ Pesanan Baru</span>
        </a>
    </div>
</form>

<!-- Tabel Daftar Pesanan Sesuai Tampilan Figma (Header Biru #0085ff) -->
<div class="bg-white rounded-3xl shadow-xs border border-slate-100 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 border-collapse">
            <!-- Header Tabel Berlatar Belakang Biru Khas Fast Wash -->
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

                        // Label status pengerjaan: diterima - diproses - selesai - diambil
                        $badgeProgres = match ($st) {
                            'diterima' => [
                                'label' => 'Diterima',
                                'class' => 'border border-blue-200 text-[#0085ff] bg-blue-50/50',
                            ],
                            'diproses' => [
                                'label' => 'Diproses',
                                'class' => 'border border-blue-300 text-[#0085ff] bg-blue-50',
                            ],
                            'selesai' => [
                                'label' => 'Selesai',
                                'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50',
                            ],
                            'diambil' => [
                                'label' => 'Diambil',
                                'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50',
                            ],
                            default => [
                                'label' => ucfirst($st),
                                'class' => 'border border-slate-200 text-slate-600 bg-slate-50',
                            ],
                        };

                        // Label status pembayaran
                        $badgeBayar = $isLunas
                            ? ['label' => 'Lunas', 'class' => 'border border-emerald-300 text-emerald-600 bg-emerald-50']
                            : ['label' => 'Belum Lunas', 'class' => 'border border-amber-300 text-amber-600 bg-amber-50'];
                        ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Kolom 1: Kode Pesanan -->
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-[#0085ff]">
                                <a href="<?php echo $base; ?>transaksi/detail?id=<?php echo (int) $row['id_pesanan']; ?>" class="hover:underline">
                                    <?php echo e($row['kode_pesanan']); ?>

                                </a>
                            </td>

                            <!-- Kolom 2: Nama Pelanggan -->
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-slate-800">
                                <?php echo e($row['nama_pelanggan']); ?>

                            </td>

                            <!-- Kolom 3: Progres Cucian (diterima - diproses - selesai - diambil) -->
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-normal <?php echo $badgeProgres['class']; ?>">
                                    <?php echo $badgeProgres['label']; ?>

                                </span>
                            </td>

                            <!-- Kolom 4: Status Pembayaran (Lunas / Belum Lunas) -->
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-normal <?php echo $badgeBayar['class']; ?>">
                                    <?php echo $badgeBayar['label']; ?>

                                </span>
                            </td>

                            <!-- Kolom 5: Aksi Update (Status & Bayar / Lihat Nota) -->
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <?php if ($st === 'diambil' && $isLunas): ?>
                                        <!-- Jika sudah Diambil & Lunas -> Tampilkan Tombol Lihat Nota -->
                                        <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $row['id_pesanan']; ?>"
                                           class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">
                                            Lihat Nota
                                        </a>
                                    <?php else: ?>
                                        <!-- Tombol Status (Ke Halaman Rincian Pesanan / Stepper) -->
                                        <a href="<?php echo $base; ?>transaksi/detail?id=<?php echo (int) $row['id_pesanan']; ?>"
                                           class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">
                                            Status
                                        </a>

                                        <!-- Tombol Bayar (Buka Modal Cepat / Pelunasan) -->
                                        <?php if (!$isLunas): ?>
                                            <button type="button"
                                                    onclick="bukaModalBayar(<?php echo (int) $row['id_pesanan']; ?>, '<?php echo e($row['kode_pesanan']); ?>', '<?php echo e($row['nama_pelanggan']); ?>', <?php echo (float) $row['total_harga']; ?>)"
                                                    class="px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors">
                                                Bayar
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer Tabel: Menampilkan X dari Total Pesanan & Navigasi Halaman -->
    <div class="px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 border-t border-slate-100">
        <div>
            Menampilkan <span class="font-bold text-slate-700"><?php echo count($daftarPesanan); ?></span> dari <span class="font-bold text-slate-700"><?php echo $totalPesanan; ?></span> pesanan
        </div>

        <!-- Paginasi Sesuai Mockup UI/UX -->
        <div class="flex items-center gap-1.5">
            <?php if ($halamanAktif > 1): ?>
                <a href="?page=<?php echo $halamanAktif - 1; ?><?php echo $cari ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?><?php echo $filterBayar ? '&bayar=' . urlencode($filterBayar) : ''; ?>"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-normal">
                    Sebelumnya
                </a>
            <?php else: ?>
                <span class="px-3 py-1.5 rounded-xl border border-slate-100 text-slate-300 font-normal cursor-not-allowed">
                    Sebelumnya
                </span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= min(5, $totalHalaman); $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $cari ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?><?php echo $filterBayar ? '&bayar=' . urlencode($filterBayar) : ''; ?>"
                   class="w-8 h-8 rounded-xl flex items-center justify-center font-medium text-xs <?php echo $i === $halamanAktif ? 'bg-[#0085ff] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'; ?>">
                    <?php echo $i; ?>

                </a>
            <?php endfor; ?>

            <?php if ($halamanAktif < $totalHalaman): ?>
                <a href="?page=<?php echo $halamanAktif + 1; ?><?php echo $cari ? '&cari=' . urlencode($cari) : ''; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?><?php echo $filterBayar ? '&bayar=' . urlencode($filterBayar) : ''; ?>"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-normal">
                    Berikutnya
                </a>
            <?php else: ?>
                <span class="px-3 py-1.5 rounded-xl border border-slate-100 text-slate-300 font-normal cursor-not-allowed">
                    Berikutnya
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Pelunasan Cepat -->
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
                <button type="button"
                        onclick="tutupModalBayar()"
                        class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 text-xs font-semibold text-white bg-[#0085ff] hover:bg-[#0070d8] rounded-xl shadow-xs transition-colors">
                    Konfirmasi Lunas
                </button>
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
