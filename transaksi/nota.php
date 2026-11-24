<?php
$title = 'Nota Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Data pesanan + pelanggan + kasir
$stmt = $pdo->prepare(
    "SELECT p.*,
            pl.nama AS nama_pelanggan,
            pl.no_telepon AS no_pelanggan,
            k.nama AS nama_karyawan
       FROM pesanan p
       JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
       LEFT JOIN karyawan k ON k.id_karyawan = p.id_karyawan
      WHERE p.id_pesanan = :id"
);
$stmt->execute(['id' => $id]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Rincian layanan
$stmtDetail = $pdo->prepare(
    "SELECT d.qty, d.harga_satuan, d.catatan, l.nama_layanan, l.satuan
       FROM detail_pesanan d
       JOIN layanan l ON l.id_layanan = d.id_layanan
      WHERE d.id_pesanan = :id
      ORDER BY d.id_pesanan ASC"
);
$stmtDetail->execute(['id' => $id]);
$daftarDetail = $stmtDetail->fetchAll();

// Transaksi pembayaran valid terakhir
$stmtTrx = $pdo->prepare(
    "SELECT * FROM riwayat_transaksi
      WHERE id_pesanan = :id AND status_transaksi = 'valid'
      ORDER BY tanggal_bayar DESC LIMIT 1"
);
$stmtTrx->execute(['id' => $id]);
$trx = $stmtTrx->fetch();

$isLunas = ($pesanan['status_bayar'] === 'lunas');

// ---------- Helper format ----------
$bulanPanjang = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$bulanPendek  = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

$tglPanjang = fn(string $d) => date('j', strtotime($d)) . ' ' . $bulanPanjang[(int) date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
$tglPendek  = fn(string $d) => date('d', strtotime($d)) . ' ' . $bulanPendek[(int) date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
$tglJam     = fn(string $d) => $tglPanjang($d) . ', ' . date('H:i', strtotime($d)) . ' WIB';
$rp         = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

$kode          = $pesanan['kode_pesanan'];
$waktuPesan    = $tglJam($pesanan['tanggal_pesan']);
$tanggalMasuk  = $tglPendek($pesanan['tanggal_pesan']);
$estimasi      = $pesanan['estimasi_selesai'] ? $tglPendek($pesanan['estimasi_selesai']) : '-';
$kasir         = $pesanan['nama_karyawan'] ?: '-';

$subtotalLayanan = (float) $pesanan['subtotal_layanan'];
$diskon          = (float) ($pesanan['diskon'] ?? 0);
$biayaTambahan   = (float) $pesanan['biaya_tambahan'];
$totalHarga      = (float) $pesanan['total_harga'];

// Metode & referensi pembayaran
$metodeKey = $trx ? strtolower($trx['jenis_pembayaran']) : '';
$metodeLabel = ['tunai' => 'TUNAI', 'qris' => 'QRIS', 'transfer' => 'TRANSFER'][$metodeKey] ?? strtoupper($metodeKey);
$waktuBayar = $trx && !empty($trx['tanggal_bayar']) ? $tglJam($trx['tanggal_bayar']) : $waktuPesan;

$noRef = $trx['no_referensi'] ?? $trx['kode_referensi'] ?? $trx['kode_transaksi'] ?? null;
if (!$noRef) {
    $prefix = ['tunai' => 'CS', 'qris' => 'QR', 'transfer' => 'TF'][$metodeKey] ?? 'TX';
    $noRef = $prefix . '-' . preg_replace('/\D/', '', $kode) . '-FW';
}

// Link WhatsApp ke pelanggan
$noWa = preg_replace('/\D/', '', (string) $pesanan['no_pelanggan']);
if (str_starts_with($noWa, '0')) {
    $noWa = '62' . substr($noWa, 1);
} elseif (str_starts_with($noWa, '8')) {
    $noWa = '628' . substr($noWa, 1);
}

$tokenNota = $pesanan['token_nota'] ?? null;
$urlNota   = '';
if (!empty($tokenNota)) {
    $notaBase = rtrim(getenv('NOTA_BASE_URL') ?: (getenv('APP_URL') ?: 'http://localhost:8000'), '/');
    $urlNota  = $notaBase . '/nota/' . $tokenNota;
}

// Hitung total pembayaran yang sah dari riwayat transaksi
$stmtSumBayar = $pdo->prepare(
    "SELECT COALESCE(SUM(jumlah_bayar), 0)
       FROM riwayat_transaksi
      WHERE id_pesanan = :id AND status_transaksi = 'valid'"
);
$stmtSumBayar->execute(['id' => $id]);
$totalBayar = (float) $stmtSumBayar->fetchColumn();
if ($totalBayar <= 0 && $isLunas) {
    $totalBayar = $totalHarga;
}

// Format komponen pesan WhatsApp
$tglPesanIso = date('Y-m-d H:i:s', strtotime($pesanan['tanggal_pesan']));
$statusBayarText = $isLunas ? 'Lunas' : ($totalBayar > 0 ? 'Sebagian' : 'Belum Lunas');
if ($metodeLabel) {
    $statusBayarText .= ' (' . $metodeLabel . ')';
}
$statusPengerjaanText = ucfirst(strtolower((string) $pesanan['status']));
$noPelangganText = !empty($pesanan['no_pelanggan']) ? " ({$pesanan['no_pelanggan']})" : '';

$estText = '-';
if (!empty($pesanan['estimasi_selesai'])) {
    $durasiHari = max(1, (int) round((strtotime($pesanan['estimasi_selesai']) - strtotime($pesanan['tanggal_pesan'])) / 86400));
    $tglEst = date('Y-m-d', strtotime($pesanan['estimasi_selesai']));
    $estText = "{$durasiHari} Hari {$tglEst}";
}
$catatanText = !empty($pesanan['catatan']) ? trim($pesanan['catatan']) : '-';

$itemsText = '';
foreach ($daftarDetail as $d) {
    $subtotalItem = (float) $d['qty'] * (float) $d['harga_satuan'];
    $satuanItem = !empty($d['satuan']) ? $d['satuan'] : 'kg';
    $qtyItem = (float) $d['qty'];
    $itemsText .= "{$d['nama_layanan']} (" . $rp($d['harga_satuan']) . " x {$qtyItem}{$satuanItem})\n"
                . $rp($subtotalItem) . "\n";
}
$itemsText = rtrim($itemsText, "\n");

$linkDigitalText = '';
if (!empty($urlNota)) {
    $linkDigitalText = "\n\nCek status laundry anda dengan mengklik link :\n" . $urlNota;
}

$pesanWa = "Fast Wash Laundry Services\n"
         . "------------------------------\n"
         . "No Nota : {$kode}\n"
         . "Tanggal : {$tglPesanIso}\n"
         . "Pembayaran : {$statusBayarText}\n"
         . "Status : {$statusPengerjaanText}\n"
         . "Nama : {$pesanan['nama_pelanggan']}{$noPelangganText}\n"
         . "Est : {$estText}\n"
         . "Note : {$catatanText}\n"
         . "------------------------------\n"
         . "{$itemsText}\n"
         . "------------------------------\n"
         . "SubTotal : " . $rp($subtotalLayanan) . "\n"
         . "Total : " . $rp($totalHarga) . "\n"
         . "Bayar : " . $rp($totalBayar) . "\n\n"
         . "------------------------------\n"
         . "Syarat dan Ketentuan:\n"
         . "1. Pengambilan cucian harus disertai nota fisik / digital.\n"
         . "2. Komplain maksimal 1x24 jam setelah cucian diambil.\n"
         . "3. Item laundryan yang tidak diambil dalam 1 bulan di luar tanggung jawab kami.\n"
         . "------------------------------"
         . $linkDigitalText;

$linkWa = 'https://wa.me/' . $noWa . '?text=' . rawurlencode($pesanWa);

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Rincian Pesanan', 'url' => 'pesanan/detail?id=' . $id],
    ['label' => 'Nota Pesanan', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .nota-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
    .nota-btn { display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 20px; border-radius: 12px; font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; box-sizing: border-box; font-family: inherit; }
    .nota-btn-wa { background: #fff; border: 1px solid #6ee7b7; color: #10b981; }
    .nota-btn-print { background: #0085ff; border: 1px solid #0085ff; color: #fff; box-shadow: 0 4px 10px rgba(0,133,255,.25); }

    .nota-card { background: #fff; border-radius: 28px; border: 1px solid #eef2f7; padding: 36px 38px; box-shadow: 0 1px 3px rgba(15,23,42,.04); margin-bottom: 40px; box-sizing: border-box; }
    .nota-dash { border: 0; border-top: 1px dashed #d1d5db; margin: 28px 0; }

    .nota-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 24px; }
    .nota-brand { display: flex; align-items: center; gap: 12px; }
    .nota-logo { width: 40px; height: 40px; border-radius: 50%; background: #0085ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .nota-brand-name { font-size: 20px; font-weight: 700; color: #0070d8; }
    .nota-addr { font-size: 11px; color: #6b7280; margin: 18px 0 0; line-height: 1.4; }
    .nota-addr + .nota-addr { margin-top: 6px; }
    .nota-addr b { font-weight: 500; color: #111827; }
    .nota-right { text-align: right; }
    .nota-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; }
    .nota-badge i { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .nota-badge-ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .nota-badge-ok i { background: #10b981; }
    .nota-badge-no { background: #fffbeb; border: 1px solid #fcd34d; color: #d97706; }
    .nota-badge-no i { background: #f59e0b; }
    .nota-kode { font-size: 28px; font-weight: 800; color: #0f172a; margin-top: 8px; letter-spacing: -0.5px; line-height: 1.2; }
    .nota-tgl { font-size: 11px; color: #9ca3af; margin-top: 4px; }

    .nota-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; text-align: center; }
    .nota-info .lbl { font-size: 12px; color: #6b7280; }
    .nota-info .val { font-size: 13px; font-weight: 700; color: #111827; margin-top: 8px; }
    .nota-info .val.biru { color: #0085ff; }

    .nota-scroll { overflow-x: auto; }
    .nota-tbl { min-width: 560px; }
    .nota-row { display: grid; grid-template-columns: 1fr 150px 150px 130px; align-items: center; }
    .nota-row > div:not(:first-child) { text-align: center; }
    .nota-thead { font-size: 13px; color: #4b5563; padding: 16px 0; border-bottom: 1px solid #f1f5f9; border-top: 1px dashed #d1d5db; }
    .nota-trow { padding: 20px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .nota-svc { display: flex; align-items: center; gap: 12px; }
    .nota-svc-ico { width: 36px; height: 36px; border-radius: 10px; background: #eef4fb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .nota-svc-nama { font-weight: 700; color: #1f2937; font-size: 13px; }
    .nota-svc-ket { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    .nota-b { font-weight: 700; color: #1f2937; }
    .nota-m { color: #4b5563; }

    .nota-pay { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 24px; align-items: start; }
    .nota-box { background: #f3f8fe; border: 1px solid #dbeafe; border-radius: 16px; padding: 18px 20px; }
    .nota-box-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .nota-box-lbl { font-size: 11px; font-weight: 700; color: #1e3a8a; letter-spacing: .5px; }
    .nota-box-ver { font-size: 10px; font-weight: 600; color: #2563eb; background: #dbe8fb; border-radius: 999px; padding: 3px 10px; white-space: nowrap; }
    .nota-box-ref { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
    .nota-box-met { font-size: 10px; font-weight: 700; color: #334155; background: #fff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 6px; }
    .nota-box-txt { font-size: 14px; font-weight: 700; color: #1f2937; }
    .nota-box-time { font-size: 11px; color: #9ca3af; margin-top: 8px; }
    .nota-box-foot { display: flex; align-items: center; gap: 8px; border-top: 1px solid #dbeafe; margin-top: 12px; padding-top: 12px; font-size: 11px; color: #6b7280; }

    .nota-tot-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-bottom: 12px; }
    .nota-tot-row span:first-child { color: #4b5563; }
    .nota-tot-row span:last-child { font-weight: 600; color: #1f2937; }
    .nota-tot-line { border: 0; border-top: 1px dashed #1f2937; margin: 4px 0 12px; }
    .nota-total { display: flex; justify-content: space-between; align-items: center; }
    .nota-total .a { font-size: 13px; font-weight: 700; color: #0085ff; }
    .nota-total .b { font-size: 24px; font-weight: 800; color: #0085ff; }

    .nota-foot { margin-top: 24px; padding-top: 20px; border-top: 1px dashed #d1d5db; }
    .nota-foot p { margin: 0; }
    .nota-catatan { font-size: 11px; color: #4b5563; }
    .nota-catatan b, .nota-sk b { color: #1f2937; font-weight: 700; }
    .nota-sk { font-size: 11px; margin-top: 8px !important; }
    .nota-sk-txt { font-size: 10.5px; color: #9ca3af; line-height: 1.6; margin-top: 4px !important; padding-left: 14px; }
    .nota-thanks { font-size: 11px; font-weight: 500; color: #0085ff; margin-top: 12px !important; }

    @media (max-width: 820px) {
        .nota-pay { grid-template-columns: 1fr; gap: 24px; }
        .nota-info { grid-template-columns: repeat(2, 1fr); }
        .nota-card { padding: 24px 20px; }
        .nota-right { text-align: left; }
    }
    @media print {
        .nota-actions, aside, nav { display: none !important; }
        body { background: #fff !important; }
        .nota-card { box-shadow: none !important; border: none !important; margin: 0; }
    }
</style>

<!-- Tombol aksi -->
<div class="nota-actions">
    <a href="<?php echo e($linkWa); ?>" target="_blank" rel="noopener" class="nota-btn nota-btn-wa">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        <span>Kirim WhatsApp</span>
    </a>
    <button type="button" onclick="window.print()" class="nota-btn nota-btn-print">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <span>Cetak Nota Fisik / Thermal</span>
    </button>
</div>

<!-- Nota -->
<div id="areaNota" class="nota-card">

    <div class="nota-head">
        <div>
            <div class="nota-brand">
                <div class="nota-logo">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round"><rect x="5" y="3" width="14" height="18" rx="3"/><circle cx="12" cy="14" r="4"/><path d="M8.5 6.5h.01M11.5 6.5h.01"/></svg>
                </div>
                <span class="nota-brand-name">Fast Wash</span>
            </div>
            <p class="nota-addr">Jl. Melati No. 12, Kota Malang</p>
            <p class="nota-addr">WhatsApp Customer Care: <b>0812-3456-7890</b></p>
        </div>

        <div class="nota-right">
            <?php if ($isLunas): ?>
                <span class="nota-badge nota-badge-ok"><i></i>LUNAS / PAID</span>
            <?php else: ?>
                <span class="nota-badge nota-badge-no"><i></i>BELUM LUNAS / UNPAID</span>
            <?php endif; ?>
            <div class="nota-kode"><?php echo e($kode); ?></div>
            <div class="nota-tgl"><?php echo e($waktuPesan); ?></div>
        </div>
    </div>

    <hr class="nota-dash">

    <div class="nota-info">
        <div><div class="lbl">Nama Pelanggan</div><div class="val"><?php echo e($pesanan['nama_pelanggan']); ?></div></div>
        <div><div class="lbl">Tanggal Masuk</div><div class="val"><?php echo e($tanggalMasuk); ?></div></div>
        <div><div class="lbl">Estimasi Selesai</div><div class="val biru"><?php echo e($estimasi); ?></div></div>
        <div><div class="lbl">Kasir / Petugas</div><div class="val"><?php echo e($kasir); ?></div></div>
    </div>

    <hr class="nota-dash" style="margin-bottom: 0;">

    <div class="nota-scroll">
        <div class="nota-tbl">
            <div class="nota-row nota-thead" style="border-top: 0;">
                <div>Layanan &amp; Deskripsi</div>
                <div>Jumlah / Berat</div>
                <div>Tarif Satuan</div>
                <div>Subtotal</div>
            </div>

            <?php foreach ($daftarDetail as $d): ?>
                <?php
                $qty      = (float) $d['qty'];
                $harga    = (float) $d['harga_satuan'];
                $satuan   = $d['satuan'] ?: 'kg';
                $subtotal = $qty * $harga;
                $ket      = trim((string) ($d['catatan'] ?? '')) ?: 'Pewangi: Ocean Fresh • Kemasan Plastik Rapi';
                ?>
                <div class="nota-row nota-trow">
                    <div class="nota-svc">
                        <div class="nota-svc-ico">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0085ff" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>
                        </div>
                        <div>
                            <div class="nota-svc-nama"><?php echo e($d['nama_layanan']); ?></div>
                            <div class="nota-svc-ket"><?php echo e($ket); ?></div>
                        </div>
                    </div>
                    <div class="nota-b"><?php echo number_format($qty, 1, '.', ''); ?> <?php echo e($satuan); ?></div>
                    <div class="nota-m"><?php echo $rp($harga); ?> / <?php echo e($satuan); ?></div>
                    <div class="nota-b"><?php echo $rp($subtotal); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="nota-pay">
        <div class="nota-box">
            <div class="nota-box-top">
                <span class="nota-box-lbl">METODE PEMBAYARAN</span>
                <?php if ($isLunas): ?><span class="nota-box-ver">Otomatis Terverifikasi</span><?php endif; ?>
            </div>

            <?php if ($isLunas): ?>
                <div class="nota-box-ref">
                    <?php if ($metodeLabel !== ''): ?><span class="nota-box-met"><?php echo e($metodeLabel); ?></span><?php endif; ?>
                    <span class="nota-box-txt">Lunas - No. Ref: <?php echo e($noRef); ?></span>
                </div>
                <div class="nota-box-time">Divalidasi pada <?php echo e($waktuBayar); ?></div>
                <div class="nota-box-foot">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="#2563eb" style="flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Bukti pembayaran sah secara elektronik oleh Fast Wash System</span>
                </div>
            <?php else: ?>
                <div class="nota-box-ref"><span class="nota-box-txt" style="color:#d97706;">Belum Lunas - Menunggu Pembayaran</span></div>
                <div class="nota-box-time">Silakan lakukan pembayaran saat pengambilan cucian.</div>
            <?php endif; ?>
        </div>

        <div>
            <div class="nota-tot-row"><span>Subtotal Layanan</span><span><?php echo $rp($subtotalLayanan); ?></span></div>
            <div class="nota-tot-row"><span>Diskon Promo</span><span><?php echo $rp($diskon); ?></span></div>
            <div class="nota-tot-row"><span>Biaya Tambahan / Antar</span><span><?php echo $rp($biayaTambahan); ?></span></div>
            <hr class="nota-tot-line">
            <div class="nota-total">
                <span class="a">Total Pembayaran</span>
                <span class="b"><?php echo $rp($totalHarga); ?></span>
            </div>
        </div>
    </div>

    <div class="nota-foot">
        <p class="nota-catatan"><b>Catatan Pesanan:</b> <?php echo e(trim((string) $pesanan['catatan']) ?: '-'); ?></p>
        <p class="nota-sk"><b>Syarat &amp; Ketentuan:</b></p>
        <p class="nota-sk-txt">Pengambilan barang wajib menunjukkan nota digital ini atau struk fisik. Klaim kehilangan atau kerusakan maksimal 1×24 jam sejak pesanan diterima. barang yang tidak diambil dalam tempo 30 hari di luar tanggung jawab kami.</p>
        <p class="nota-thanks">Terima kasih telah mempercayakan laundry Anda kepada Fast Wash!</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
