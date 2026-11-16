<?php
$title = 'Riwayat Transaksi';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Riwayat Transaksi', 'url' => ''],
];

$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT p.id_pesanan,
           p.kode_pesanan,
           pl.nama AS nama_pelanggan,
           pl.no_telepon,
           p.status,
           p.status_bayar,
           p.total_harga,
           p.tanggal_pesan,
           p.waktu_selesai,
           t.jenis_pembayaran,
           t.tanggal_bayar,
           (SELECT string_agg(l.nama_layanan || ' (' || d.qty || ' ' || l.satuan || ')', ', ')
              FROM detail_pesanan d
              JOIN layanan l ON l.id_layanan = d.id_layanan
             WHERE d.id_pesanan = p.id_pesanan) AS rincian_layanan
      FROM pesanan p
      JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
      LEFT JOIN LATERAL (
          SELECT x.jenis_pembayaran, x.tanggal_bayar
            FROM riwayat_transaksi x
           WHERE x.id_pesanan = p.id_pesanan AND x.status_transaksi = 'valid'
           ORDER BY x.tanggal_bayar DESC
           LIMIT 1
      ) t ON true
     WHERE (p.status = 'diambil' OR p.status_bayar = 'lunas')
";

$params = [];
if ($cari !== '') {
    $sql .= " AND (p.kode_pesanan ILIKE :cari OR pl.nama ILIKE :cari OR pl.no_telepon ILIKE :cari)";
    $params['cari'] = '%' . $cari . '%';
}

$sql .= " ORDER BY p.tanggal_pesan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Riwayat Transaksi</h1>
        <p class="text-sm text-slate-500 mt-0.5">Daftar transaksi pesanan laundry yang telah selesai atau lunas</p>
    </div>
    <div>
        <a href="<?php echo $base; ?>pesanan" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition-colors">
            &larr; Ke Daftar Pesanan Aktif
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-100 mb-6">
    <form method="get" class="flex gap-3">
        <div class="relative flex-1">
            <input type="text"
                   name="cari"
                   value="<?php echo e($cari); ?>"
                   placeholder="Cari kode pesanan atau nama pelanggan..."
                   class="w-full px-4 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-[#0085ff]/30">
        </div>
        <button type="submit" class="px-5 py-2.5 bg-[#0085ff] hover:bg-[#0070d8] text-white text-xs font-bold rounded-xl transition-colors">
            Cari
        </button>
        <?php if ($cari !== ''): ?>
            <a href="<?php echo $base; ?>transaksi/riwayat" class="px-3 py-2.5 text-xs text-slate-500 hover:text-rose-600 font-semibold self-center">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-xs border border-slate-100 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 border-collapse">
            <thead class="bg-slate-50/80 border-b border-slate-100 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="py-4 px-5">No. Pesanan</th>
                    <th class="py-4 px-5">Pelanggan</th>
                    <th class="py-4 px-5 hidden md:table-cell">Layanan</th>
                    <th class="py-4 px-5">Total</th>
                    <th class="py-4 px-5">Metode</th>
                    <th class="py-4 px-5 hidden sm:table-cell">Waktu Masuk</th>
                    <th class="py-4 px-5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($riwayat)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            Belum ada riwayat transaksi yang terselesaikan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($riwayat as $r): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-5 whitespace-nowrap font-bold text-[#0085ff]">
                                <a href="<?php echo $base; ?>pesanan/detail?id=<?php echo (int) $r['id_pesanan']; ?>" class="hover:underline">
                                    <?php echo e($r['kode_pesanan']); ?>

                                </a>
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">
                                <div class="font-semibold text-slate-800"><?php echo e($r['nama_pelanggan']); ?></div>
                                <div class="text-xs text-slate-400"><?php echo e($r['no_telepon']); ?></div>
                            </td>
                            <td class="py-4 px-5 hidden md:table-cell text-xs text-slate-600 max-w-xs truncate">
                                <?php echo e($r['rincian_layanan'] ?: '-'); ?>

                            </td>
                            <td class="py-4 px-5 whitespace-nowrap font-bold text-slate-800">
                                Rp <?php echo number_format((float) $r['total_harga'], 0, ',', '.'); ?>

                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-blue-50 text-[#0085ff]">
                                    <?php echo e($r['jenis_pembayaran'] ?: 'Tunai'); ?>

                                </span>
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap hidden sm:table-cell text-xs text-slate-500">
                                <?php echo date('d M Y, H:i', strtotime($r['tanggal_pesan'])); ?>

                            </td>
                            <td class="py-4 px-5 whitespace-nowrap text-right">
                                <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $r['id_pesanan']; ?>"
                                   class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors">
                                    Nota
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
