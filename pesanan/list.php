<?php
$title = 'Daftar Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$listPath     = 'pesanan';
$detailPath   = 'pesanan/detail';
$newOrderPath = 'pesanan/tambah';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => $listPath],
    ['label' => 'Daftar Pesanan', 'url' => ''],
];

// Filter & pencarian
$cari         = trim($_GET['cari'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterBayar  = trim($_GET['bayar'] ?? '');

// Pagination (5 baris per halaman, sesuai desain)
$halamanAktif = max(1, (int) ($_GET['page'] ?? 1));
$limit        = 5;
$offset       = ($halamanAktif - 1) * $limit;

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

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pesanan p JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan" . $sqlWhere);
$stmtCount->execute($params);
$totalPesanan = (int) $stmtCount->fetchColumn();
$totalHalaman = max(1, (int) ceil($totalPesanan / $limit));

$sql = "SELECT p.id_pesanan,
               p.kode_pesanan,
               pl.nama AS nama_pelanggan,
               p.status AS progres_cucian,
               p.status_bayar AS status_pembayaran,
               p.total_harga
          FROM pesanan p
          JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
          {$sqlWhere}
         ORDER BY p.tanggal_pesan DESC, p.id_pesanan DESC
         LIMIT {$limit} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarPesanan = $stmt->fetchAll();

$flashSukses = $_SESSION['flash_sukses'] ?? null;
$flashError  = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

// URL pagination
$qs = fn(int $page) => $base . $listPath . '?page=' . $page
    . '&cari=' . urlencode($cari) . '&status=' . urlencode($filterStatus) . '&bayar=' . urlencode($filterBayar);

$btnAksi = 'px-4 py-1.5 text-xs text-slate-600 font-normal bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:border-slate-300 transition-colors';

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($flashSukses): ?>
    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <span><?php echo e($flashSukses); ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <span><?php echo e($flashError); ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<!-- Pencarian | Status Cucian | Status Pembayaran | + Pesanan Baru -->
<?php
$styleKotak  = 'height: 40px; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.04); font-size: 14px; color: #1F2937; outline: none; box-sizing: border-box;';
$styleSelect = $styleKotak . ' appearance: none; -webkit-appearance: none; -moz-appearance: none; padding: 0 40px 0 16px; cursor: pointer; font-weight: 400;';
$iconChevron = '<svg width="16" height="16" fill="none" stroke="#6B7280" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
?>
<form method="get" action="<?php echo $base . $listPath; ?>" style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 24px;">

    <!-- Pencarian -->
    <div style="position: relative; width: 373px; max-width: 100%; flex: 0 1 373px;">
        <div style="position: absolute; top: 0; bottom: 0; left: 14px; display: flex; align-items: center; pointer-events: none;">
            <svg width="16" height="16" fill="none" stroke="#9CA3AF" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
        </div>
        <input type="text" name="cari" value="<?php echo e($cari); ?>" placeholder="Cari Nama Pelanggan / Kode Pesanan"
               style="<?php echo $styleKotak; ?> width: 100%; padding: 0 40px 0 40px;">
        <div style="position: absolute; top: 0; bottom: 0; right: 14px; display: flex; align-items: center; pointer-events: none;"><?php echo $iconChevron; ?></div>
    </div>

    <!-- Status Cucian -->
    <div style="position: relative; width: 158px; flex: 0 0 158px;">
        <select name="status" onchange="this.form.submit()" style="<?php echo $styleSelect; ?> width: 100%;">
            <option value="">Status Cucian</option>
            <?php foreach (['diterima' => 'Diterima', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'diambil' => 'Diambil'] as $v => $l): ?>
                <option value="<?php echo $v; ?>" <?php echo $filterStatus === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
            <?php endforeach; ?>
        </select>
        <div style="position: absolute; top: 0; bottom: 0; right: 14px; display: flex; align-items: center; pointer-events: none;"><?php echo $iconChevron; ?></div>
    </div>

    <!-- Status Pembayaran -->
    <div style="position: relative; min-width: 205px; flex: 0 0 auto;">
        <select name="bayar" onchange="this.form.submit()" style="<?php echo $styleSelect; ?> width: 100%; white-space: nowrap;">
            <option value="">Status Pembayaran</option>
            <option value="belum_lunas" <?php echo $filterBayar === 'belum_lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
            <option value="lunas" <?php echo $filterBayar === 'lunas' ? 'selected' : ''; ?>>Lunas</option>
        </select>
        <div style="position: absolute; top: 0; bottom: 0; right: 14px; display: flex; align-items: center; pointer-events: none;"><?php echo $iconChevron; ?></div>
    </div>

    <?php if ($cari !== '' || $filterStatus !== '' || $filterBayar !== ''): ?>
        <a href="<?php echo $base . $listPath; ?>" style="height: 40px; display: inline-flex; align-items: center; padding: 0 12px; font-size: 14px; font-weight: 600; color: #F43F5E; white-space: nowrap; box-sizing: border-box;">Reset</a>
    <?php endif; ?>

    <!-- Tombol Pesanan Baru -->
    <a href="<?php echo $base . $newOrderPath; ?>"
       style="margin-left: auto; height: 40px; display: inline-flex; align-items: center; gap: 8px; padding: 0 22px; background-color: #0085FF; color: #FFFFFF; font-size: 14px; font-weight: 500; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,133,255,0.25); white-space: nowrap; box-sizing: border-box;">
        <svg width="16" height="16" fill="none" stroke="#FFFFFF" stroke-width="2.5" viewBox="0 0 24 24" style="display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        <span>Pesanan Baru</span>
    </a>
</form>

<!-- Tabel -->
<div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 border-collapse">
            <thead class="bg-[#0085ff] text-white text-sm">
                <tr>
                    <th scope="col" class="py-4 px-6 font-medium">Kode</th>
                    <th scope="col" class="py-4 px-6 font-medium">Nama Pelanggan</th>
                    <th scope="col" class="py-4 px-6 font-medium text-center">Progres Cucian</th>
                    <th scope="col" class="py-4 pl-10 pr-8 font-medium text-right whitespace-nowrap">Status Pembayaran</th>
                    <th scope="col" class="py-4 px-6 font-medium text-center">Update</th>
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
                        $st      = strtolower($row['progres_cucian']);
                        $isLunas = ($row['status_pembayaran'] === 'lunas');

                        $badgeProgres = match ($st) {
                            'diterima' => ['Diterima', 'border border-blue-200 text-sky-600 bg-sky-50'],
                            'diproses' => ['Diproses', 'border border-blue-200 text-sky-600 bg-sky-50'],
                            'selesai'  => ['Selesai',  'border border-blue-300 text-blue-700 bg-blue-50'],
                            'diambil'  => ['Diambil',  'border border-emerald-200 text-emerald-700 bg-emerald-50'],
                            default    => [ucfirst($st), 'border border-slate-200 text-slate-600 bg-slate-50'],
                        };
                        $badgeBayar = $isLunas
                            ? ['Lunas', 'border border-emerald-200 text-emerald-700 bg-emerald-50']
                            : ['Belum Lunas', 'border border-amber-200 text-amber-600 bg-amber-50'];
                        ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-[#0a5cc7]">
                                <a href="<?php echo $base . $detailPath; ?>?id=<?php echo (int) $row['id_pesanan']; ?>" class="hover:underline"><?php echo e($row['kode_pesanan']); ?></a>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap font-medium text-slate-800"><?php echo e($row['nama_pelanggan']); ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-medium <?php echo $badgeProgres[1]; ?>"><?php echo $badgeProgres[0]; ?></span>
                            </td>
                            <td class="py-4 pl-10 pr-8 whitespace-nowrap text-right">
                                <span class="inline-block px-5 py-1 rounded-full text-xs font-medium <?php echo $badgeBayar[1]; ?>"><?php echo $badgeBayar[0]; ?></span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <?php if ($isLunas && $st === 'diambil'): ?>
                                        <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $row['id_pesanan']; ?>" class="<?php echo $btnAksi; ?>">Lihat Nota</a>
                                    <?php else: ?>
                                        <a href="<?php echo $base . $detailPath; ?>?id=<?php echo (int) $row['id_pesanan']; ?>" class="<?php echo $btnAksi; ?>">Status</a>
                                        <?php if ($isLunas): ?>
                                            <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $row['id_pesanan']; ?>" class="<?php echo $btnAksi; ?>">Lihat Nota</a>
                                        <?php else: ?>
                                            <button type="button"
                                                    onclick="bukaModalBayar(<?php echo (int) $row['id_pesanan']; ?>, '<?php echo e($row['kode_pesanan']); ?>', '<?php echo e($row['nama_pelanggan']); ?>', <?php echo (float) $row['total_harga']; ?>)"
                                                    class="<?php echo $btnAksi; ?>">Bayar</button>
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

    <?php if ($totalPesanan > 0): ?>
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 text-xs text-slate-500">
            <span>Menampilkan <strong class="text-slate-700"><?php echo count($daftarPesanan); ?></strong> dari <strong class="text-slate-700"><?php echo $totalPesanan; ?></strong> pesanan</span>
            <div class="flex items-center gap-1.5">
                <?php if ($halamanAktif > 1): ?>
                    <a href="<?php echo $qs($halamanAktif - 1); ?>" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">Sebelumnya</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-300 rounded-lg">Sebelumnya</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                    <?php if ($i === $halamanAktif): ?>
                        <span class="px-3 py-1.5 bg-[#0085ff] text-white font-semibold rounded-lg"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $qs($i); ?>" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($halamanAktif < $totalHalaman): ?>
                    <a href="<?php echo $qs($halamanAktif + 1); ?>" class="px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">Berikutnya</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-300 rounded-lg">Berikutnya</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pembayaran -->
<style>
    .opsi-metode {
        position: relative;
        display: flex; align-items: center; justify-content: center;
        height: 56px; border: 2px solid #E5E7EB; border-radius: 20px;
        background: #FFFFFF; cursor: pointer; transition: border-color .15s;
        font-size: 15px; font-weight: 400; color: #334155;
    }
    .opsi-metode:hover { border-color: #CBD5E1; }
    .opsi-metode:has(input:checked) { border-color: #0085FF; }
    .opsi-metode input {
        position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none;
    }
</style>

<div id="modalBayar" class="fixed inset-0 z-50 hidden bg-slate-900/40 backdrop-blur-xs items-center justify-center p-4">
    <div style="background:#FFFFFF; border-radius:28px; width:100%; max-width:520px; padding:28px; box-shadow:0 20px 50px rgba(15,23,42,.25);">

        <!-- Judul + tombol tutup -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h3 style="font-size:20px; font-weight:700; color:#1E293B; margin:0;">Konfirmasi Pembayaran</h3>
            <button type="button" onclick="tutupModalBayar()" aria-label="Tutup"
                    style="color:#94A3B8; background:none; border:0; cursor:pointer; padding:4px; display:flex;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="<?php echo $base; ?>transaksi/bayar" method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id_pesanan" id="modalIdPesanan" value="">

            <!-- Ringkasan -->
            <div style="background:#F0F6FE; border-radius:18px; padding:20px 22px; margin-bottom:24px; display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px;">
                    <span style="color:#64748B;">Kode Pesanan</span>
                    <span id="modalKodePesanan" style="font-weight:700; color:#0A7DE0;">-</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px;">
                    <span style="color:#64748B;">Pelanggan</span>
                    <span id="modalNamaPelanggan" style="color:#0F172A;">-</span>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:15px;">
                    <span style="color:#64748B;">Total Tagihan</span>
                    <span id="modalTotalHarga" style="font-weight:700; font-size:20px; color:#0A7DE0;">Rp 0</span>
                </div>
            </div>

            <!-- Metode pembayaran -->
            <p style="font-size:15px; font-weight:700; color:#334155; margin:0 0 12px;">Metode Pembayaran</p>
            <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:28px;">
                <?php foreach (['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $v => $l): ?>
                    <label class="opsi-metode">
                        <input type="radio" name="metode" value="<?php echo $v; ?>" required>
                        <span><?php echo $l; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Aksi -->
            <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
                <button type="button" onclick="tutupModalBayar()"
                        style="padding:12px 20px; font-size:15px; color:#475569; background:none; border:0; cursor:pointer; border-radius:14px;">Batal</button>
                <button type="submit"
                        style="padding:14px 28px; font-size:15px; font-weight:500; color:#FFFFFF; background:#0085FF; border:0; cursor:pointer; border-radius:14px;">Konfirmasi Lunas</button>
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
        document.querySelectorAll('#modalBayar input[name="metode"]').forEach(r => r.checked = false);
        const m = document.getElementById('modalBayar');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function tutupModalBayar() {
        const m = document.getElementById('modalBayar');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>