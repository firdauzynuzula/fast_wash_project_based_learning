<?php
$title = 'Rincian Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Pesanan yang dibuka (jangkar)
$stmtAnchor = $pdo->prepare("SELECT id_pesanan, id_pelanggan, tanggal_pesan FROM pesanan WHERE id_pesanan = :id");
$stmtAnchor->execute(['id' => $id]);
$anchor = $stmtAnchor->fetch();

if (!$anchor) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Semua pesanan dalam 1 kali transaksi:
// pelanggan sama + waktu pesan sama (NOW() di PostgreSQL identik dalam satu transaksi database)
$stmtGrup = $pdo->prepare(
    "SELECT p.*,
            pl.nama AS nama_pelanggan,
            (SELECT string_agg(l.nama_layanan, ', ')
               FROM detail_pesanan d
               JOIN layanan l ON l.id_layanan = d.id_layanan
              WHERE d.id_pesanan = p.id_pesanan) AS nama_layanan,
            (SELECT COALESCE(SUM(d.qty), 0)
               FROM detail_pesanan d
              WHERE d.id_pesanan = p.id_pesanan) AS total_qty
       FROM pesanan p
       JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
      WHERE p.id_pelanggan = :pel AND p.tanggal_pesan = :tgl
      ORDER BY p.id_pesanan ASC"
);
$stmtGrup->execute(['pel' => $anchor['id_pelanggan'], 'tgl' => $anchor['tanggal_pesan']]);
$daftarPesanan = $stmtGrup->fetchAll();

if (!$daftarPesanan) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

$pesananPertama = $daftarPesanan[0];

// Rincian layanan per pesanan (untuk ringkasan di kolom kanan)
$stmtRincian = $pdo->prepare(
    "SELECT d.qty, d.harga_satuan, d.catatan, l.nama_layanan
       FROM detail_pesanan d
       JOIN layanan l ON l.id_layanan = d.id_layanan
      WHERE d.id_pesanan = :id
      ORDER BY d.id_detail ASC"
);
$maxJam = 0;
foreach ($daftarPesanan as $k => $ps) {
    try {
        $stmtRincian->execute(['id' => $ps['id_pesanan']]);
        $daftarPesanan[$k]['rincian'] = $stmtRincian->fetchAll();
    } catch (Exception $e) {
        // fallback bila kolom id_detail tidak ada
        $stmtAlt = $pdo->prepare(
            "SELECT d.qty, d.harga_satuan, d.catatan, l.nama_layanan
               FROM detail_pesanan d JOIN layanan l ON l.id_layanan = d.id_layanan
              WHERE d.id_pesanan = :id"
        );
        $stmtAlt->execute(['id' => $ps['id_pesanan']]);
        $daftarPesanan[$k]['rincian'] = $stmtAlt->fetchAll();
    }
    if (!empty($ps['estimasi_selesai'])) {
        $jam = (int) round((strtotime($ps['estimasi_selesai']) - strtotime($ps['tanggal_pesan'])) / 3600);
        $maxJam = max($maxJam, $jam);
    }
}
$pesananSiap = $maxJam <= 0 ? '' : ($maxJam < 24 ? "dalam {$maxJam} jam" : 'dalam ' . (int) ceil($maxJam / 24) . ' hari');
$rpEn = fn($n) => number_format((float) $n, 0, '.', ',');

// Ringkasan gabungan untuk kolom kanan
$semuaLunas  = true;
$totalBerat  = 0.0;
$totalHarga  = 0.0;
$namaLayanan = [];
foreach ($daftarPesanan as $ps) {
    if ($ps['status_bayar'] !== 'lunas') {
        $semuaLunas = false;
    }
    $totalBerat += (float) $ps['total_qty'];
    $totalHarga += (float) $ps['total_harga'];
    foreach (explode(', ', (string) $ps['nama_layanan']) as $nm) {
        if ($nm !== '' && !in_array($nm, $namaLayanan, true)) {
            $namaLayanan[] = $nm;
        }
    }
}

// Metode pembayaran (dari transaksi valid pertama)
$stmtTrx = $pdo->prepare(
    "SELECT * FROM riwayat_transaksi
      WHERE id_pesanan = :id AND status_transaksi = 'valid'
      ORDER BY tanggal_bayar DESC LIMIT 1"
);
$stmtTrx->execute(['id' => $pesananPertama['id_pesanan']]);
$trxBayar = $stmtTrx->fetch();

$labelMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];
$metodeKey   = $trxBayar ? strtolower($trxBayar['jenis_pembayaran']) : '';
$metodeText  = $labelMetode[$metodeKey] ?? '';
$teksBayar   = $semuaLunas ? (($metodeText !== '' ? $metodeText . ' – ' : '') . 'Lunas') : 'Belum Lunas';

// Alur status
$urutan  = ['diterima' => 1, 'diproses' => 2, 'selesai' => 3, 'diambil' => 4];
$nextMap = ['diterima' => 'diproses', 'diproses' => 'selesai', 'selesai' => 'diambil', 'diambil' => null];
$prevMap = ['diterima' => null, 'diproses' => 'diterima', 'selesai' => 'diproses', 'diambil' => 'selesai'];

// Pesanan yang baru diubah statusnya (untuk tombol Undo di notifikasi)
$statusAnchor   = strtolower((string) ($pdo->query("SELECT status FROM pesanan WHERE id_pesanan = " . (int) $anchor['id_pesanan'])->fetchColumn()));
$previousStatus = $prevMap[$statusAnchor] ?? null;

// Tanggal format Indonesia
$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$ts = strtotime($pesananPertama['tanggal_pesan']);
$tanggalPesan = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);

$flashSukses = $_SESSION['flash_sukses'] ?? null;
$flashError  = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Rincian Pesanan', 'url' => ''],
];

// Ikon stepper
function ikonStep(string $tipe, string $warna): string
{
    switch ($tipe) {
        case 'check':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="display:block;"><path d="M5 13l4 4L19 7" stroke="' . $warna . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'mesin':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="display:block;"><rect x="4" y="2" width="16" height="20" rx="3" stroke="' . $warna . '" stroke-width="2.4"/><circle cx="12" cy="14" r="4" stroke="' . $warna . '" stroke-width="2.4"/><path d="M8 6h.01M12 6h.01" stroke="' . $warna . '" stroke-width="2.6" stroke-linecap="round"/></svg>';
        case 'keranjang':
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="display:block;"><rect x="3" y="4" width="18" height="5" rx="1.5" stroke="' . $warna . '" stroke-width="2.4"/><path d="M5 9v9a2 2 0 002 2h10a2 2 0 002-2V9M10 13h4" stroke="' . $warna . '" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        default:
            return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="display:block;"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="' . $warna . '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
}

// Ilustrasi mesin cuci
function ilustrasiCuci(): string
{
    return '<svg width="110" height="64" viewBox="0 0 160 92" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
        <rect x="34" y="4" width="62" height="78" rx="8" fill="#F0F9FF" stroke="#93C5FD" stroke-width="2"/>
        <rect x="34" y="4" width="62" height="10" rx="3" fill="#E0F2FE"/>
        <circle cx="65" cy="46" r="19" fill="#FFFFFF" stroke="#93C5FD" stroke-width="2.5"/>
        <circle cx="65" cy="46" r="14" fill="#0085FF"/>
        <circle cx="65" cy="46" r="6" fill="#2563EB"/>
        <circle cx="44" cy="9" r="2" fill="#38BDF8"/>
        <path d="M104 46h38l-4 32a5 5 0 01-5 4h-20a5 5 0 01-5-4z" fill="#BAE6FD" stroke="#38BDF8" stroke-width="2"/>
        <circle cx="118" cy="42" r="8" fill="#E0F2FE"/>
        <circle cx="130" cy="40" r="7" fill="#F0F9FF"/>
        <rect x="12" y="62" width="14" height="10" rx="2" fill="#F59E0B"/>
        <path d="M18 62c-5-6-3-12 0-16 3 4 5 10 0 16z" fill="#4ADE80"/>
        <rect x="100" y="14" width="6" height="12" rx="2" fill="#38BDF8"/>
    </svg>';
}

$stepNodes = [
    ['label' => 'Diterima', 'icon' => 'check'],
    ['label' => 'Diproses', 'icon' => 'mesin'],
    ['label' => 'Selesai',  'icon' => 'keranjang'],
    ['label' => 'Diambil',  'icon' => 'kotak'],
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .dt-grid { display: grid; grid-template-columns: 2.08fr 1fr; gap: 24px; align-items: start; }
    .dt-left { display: flex; flex-direction: column; gap: 24px; min-width: 0; }
    .dt-card { background: #fff; border-radius: 20px; padding: 22px 24px 24px; box-shadow: 0 1px 3px rgba(0,0,0,.05); box-sizing: border-box; }
    .dt-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .dt-pill { display: inline-block; padding: 4px 14px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .dt-pill.pending { background: #FEF3C7; color: #D97706; }
    .dt-pill.selesai { background: #D1FAE5; color: #047857; }
    .dt-nama { font-size: 20px; font-weight: 600; color: #111827; line-height: 1.3; margin: 10px 0 0; }
    .dt-meta { font-size: 12px; color: #9CA3AF; margin: 4px 0 0; }
    .dt-inner { margin-top: 18px; border: 1px solid #EEF2F7; border-radius: 18px; padding: 22px 24px 24px; box-shadow: 0 1px 3px rgba(0,0,0,.03); }
    .dt-inner-head { display: flex; align-items: center; justify-content: space-between; }
    .dt-inner-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }
    .dt-badge { padding: 5px 14px; border-radius: 9999px; font-size: 11px; font-weight: 500; background: #E0F2FE; color: #0085FF; }
    .dt-stepper { display: flex; margin: 30px 0 32px; }
    .dt-node { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
    .dt-line { position: absolute; top: 13px; right: 50%; width: 100%; height: 2px; z-index: 0; }
    .dt-circle { position: relative; z-index: 1; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .dt-node-label { margin-top: 8px; font-size: 12px; font-weight: 400; }
    .dt-btn { display: block; width: 100%; padding: 12px 20px; border-radius: 12px; border: none; background: #0085FF; color: #fff; font-size: 14px; font-weight: 500; text-align: center; cursor: pointer; font-family: inherit; }
    .dt-btn:hover { background: #0070d8; }
    .dt-done { display: flex; align-items: center; justify-content: center; gap: 8px; color: #15803D; font-size: 13px; font-weight: 600; padding: 12px 0 0; }

    .dt-right { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
    .dt-add { display: flex; align-items: center; justify-content: center; height: 46px; box-sizing: border-box; border: 1.5px solid #0085FF; border-radius: 14px; background: #fff; color: #0085FF; font-size: 13px; font-weight: 600; text-decoration: none; }
    .dt-add:hover { background: #f0f7ff; }
    .dt-sum { background: #fff; border-radius: 16px; padding: 22px 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
    .dt-sum-title { font-size: 14px; font-weight: 700; color: #111827; margin: 0; padding-bottom: 16px; border-bottom: 1px solid #EEF2F7; margin-bottom: 18px; }
    .dt-row { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; margin-bottom: 14px; line-height: 1.3; min-height: 16px; }
    .dt-row .k { color: #374151; }
    .dt-row .v { color: #111827; font-weight: 500; text-align: right; max-width: 65%; word-break: break-word; overflow-wrap: anywhere; }
    .dt-sep { border: 0; border-top: 1px solid #EEF2F7; margin: 4px 0 14px; }
    .dt-total { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 20px; border-top: 1px solid #EEF2F7; }
    .dt-total .k { font-size: 13px; font-weight: 700; color: #111827; }
    .dt-total .v { font-size: 15px; font-weight: 700; color: #0085FF; }
    .dt-nota { display: block; margin-top: 22px; padding: 11px 16px; border-radius: 12px; border: 1px solid #0085FF; color: #0085FF; font-weight: 600; font-size: 12px; text-align: center; background: #fff; text-decoration: none; }
    .dt-semua { display: block; margin-top: 14px; text-align: center; font-size: 12px; color: #6B7280; font-weight: 500; text-decoration: none; }

    @media (max-width: 900px) {
        .dt-grid { grid-template-columns: 1fr; }
        .dt-top svg { display: none; }
    }
</style>

<!-- Alert notifikasi flash -->
<?php if ($flashSukses): ?>
    <div style="margin-bottom: 20px; padding: 16px; background-color: #ECFDF5; border: 1px solid #BBF7D0; color: #166534; font-size: 14px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 20 20" style="flex-shrink: 0;">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" fill="#166534"/>
            </svg>
            <span style="font-weight: 500;"><?php echo e($flashSukses); ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <?php if ($previousStatus): ?>
                <form action="<?php echo $base; ?>pesanan/proses_undo" method="post" style="display: flex; align-items: center;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $anchor['id_pesanan']; ?>">
                    <button type="submit" style="color: #16A34A; font-weight: 700; font-size: 12px; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; padding: 4px; background: none; border: 0;">Undo</button>
                </form>
            <?php endif; ?>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="color: #65A30D; font-weight: 700; cursor: pointer; padding: 4px; background: none; border: 0;">&times;</button>
        </div>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div style="margin-bottom: 20px; padding: 16px; background-color: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; font-size: 14px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 20 20" style="flex-shrink: 0;">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" fill="#92400E"/>
            </svg>
            <span style="font-weight: 500;"><?php echo e($flashError); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="color: #CA8A04; font-weight: 700; cursor: pointer; padding: 4px; background: none; border: 0;">&times;</button>
    </div>
<?php endif; ?>

<div class="dt-grid">

    <!-- Kolom kiri: satu kartu progres per pesanan dalam transaksi ini -->
    <div class="dt-left">
        <?php foreach ($daftarPesanan as $ps): ?>
            <?php
            $st       = strtolower($ps['status']);
            $stepNow  = $urutan[$st] ?? 1;
            $nextSt   = $nextMap[$st] ?? null;
            $idPs     = (int) $ps['id_pesanan'];
            ?>
            <div class="dt-card">
                <div class="dt-top">
                    <div>
                        <?php if ($st === 'diambil'): ?>
                            <span class="dt-pill selesai">Pesanan Selesai</span>
                        <?php else: ?>
                            <span class="dt-pill pending">Pending</span>
                        <?php endif; ?>
                        <h1 class="dt-nama"><?php echo e($ps['nama_layanan'] ?: 'Cuci + Setrika'); ?></h1>
                        <p class="dt-meta">
                            <?php echo e($ps['kode_pesanan']); ?> · <?php echo e($ps['nama_pelanggan']); ?> · <?php echo (float) $ps['total_qty']; ?> kg · pesanan dibuat <?php echo $tanggalPesan; ?>
                        </p>
                    </div>
                    <div style="flex-shrink: 0;"><?php echo ilustrasiCuci(); ?></div>
                </div>

                <div class="dt-inner">
                    <div class="dt-inner-head">
                        <h2 class="dt-inner-title">Status Pengerjaan</h2>
                        <span class="dt-badge"><?php echo ucfirst($st); ?></span>
                    </div>

                    <div class="dt-stepper">
                        <?php foreach ($stepNodes as $idx => $node): ?>
                            <?php
                            $stepNum = $idx + 1;
                            $isDone  = ($stepNum <= $stepNow);
                            ?>
                            <div class="dt-node">
                                <?php if ($idx > 0): ?>
                                    <div class="dt-line" style="background-color: <?php echo $isDone ? '#0085FF' : '#E5E7EB'; ?>;"></div>
                                <?php endif; ?>
                                <div class="dt-circle" style="background-color: <?php echo $isDone ? '#0085FF' : '#E8EEF5'; ?>;">
                                    <?php echo ikonStep($node['icon'], $isDone ? '#FFFFFF' : '#9CA3AF'); ?>
                                </div>
                                <span class="dt-node-label" style="color: <?php echo $isDone ? '#111827' : '#6B7280'; ?>;"><?php echo $node['label']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($nextSt): ?>
                        <form id="formUbah<?php echo $idPs; ?>" action="<?php echo $base; ?>pesanan/proses_edit" method="post">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="id_pesanan" value="<?php echo $idPs; ?>">
                            <input type="hidden" name="status_tujuan" value="<?php echo e($nextSt); ?>">
                            <button type="button" class="dt-btn"
                                    onclick="bukaModalKonfirmasi('formUbah<?php echo $idPs; ?>', '<?php echo e(ucfirst($st)); ?>', '<?php echo e(ucfirst($nextSt)); ?>')">
                                Ubah Ke <?php echo ucfirst($nextSt); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Kolom kanan -->
    <div class="dt-right">
        <a href="<?php echo $base; ?>pesanan/tambah" class="dt-add">+ Tambah Pesanan</a>

        <div class="dt-sum">
            <h2 class="dt-sum-title">Ringkasan Pesanan</h2>

            <?php foreach ($daftarPesanan as $n => $ps): ?>
                <?php
                $namaLay = []; $berat = []; $notes = [];
                foreach ($ps['rincian'] as $r) {
                    $namaLay[] = $r['nama_layanan'];
                    $q = (float) $r['qty'];
                    $berat[] = $q . ' kg × Rp ' . $rpEn($r['harga_satuan']);
                    if (trim((string) $r['catatan']) !== '') {
                        $notes[] = trim($r['catatan']);
                    }
                }
                $cepat = ((float) $ps['biaya_tambahan']) > 0;
                ?>
                <?php if ($n > 0): ?><hr class="dt-sep"><?php endif; ?>
                <div class="dt-row"><span class="k">Pelanggan</span><span class="v"><?php echo e($ps['nama_pelanggan']); ?></span></div>
                <div class="dt-row"><span class="k">Layanan</span><span class="v"><?php echo e(implode(', ', $namaLay) ?: '-'); ?></span></div>
                <div class="dt-row"><span class="k">Berat</span><span class="v"><?php echo e(implode(', ', $berat) ?: '-'); ?></span></div>
                <div class="dt-row"><span class="k">Layanan Cepat</span><span class="v"><?php echo $cepat ? $rpEn($ps['biaya_tambahan']) : '-'; ?></span></div>
                <div class="dt-row"><span class="k">Catatan</span><span class="v"><?php echo e(implode(', ', $notes)); ?></span></div>
            <?php endforeach; ?>

            <?php if (count($daftarPesanan) > 1): ?><hr class="dt-sep"><?php endif; ?>
            <div class="dt-row"><span class="k">Pembayaran</span><span class="v"><?php echo e($metodeText ?: '-'); ?></span></div>
            <div class="dt-row"><span class="k">Status Pembayaran</span><span class="v"><?php echo $semuaLunas ? 'Lunas' : 'Belum Lunas'; ?></span></div>
            <div class="dt-row"><span class="k">Pesanan Siap</span><span class="v"><?php echo e($pesananSiap); ?></span></div>

            <div class="dt-total">
                <span class="k">Total</span>
                <span class="v">Rp <?php echo $rpEn($totalHarga); ?></span>
            </div>

            <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $pesananPertama['id_pesanan']; ?>" class="dt-nota">Cetak Nota</a>
            <a href="<?php echo $base; ?>pesanan" class="dt-semua">Semua Pesanan</a>
        </div>
    </div>
</div>

<!-- Popup konfirmasi ubah status -->
<div id="modalKonfirmasi" onclick="if (event.target === this) tutupModalKonfirmasi()"
     style="display: none; position: fixed; inset: 0; z-index: 50; background-color: rgba(15,23,42,0.4); align-items: center; justify-content: center; padding: 16px;">
    <div style="background-color: #FFFFFF; border-radius: 20px; width: 100%; max-width: 380px; padding: 28px 24px 24px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
        <div style="width: 48px; height: 48px; margin: 0 auto; border-radius: 50%; background-color: #E0F2FE; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#0085FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin-top: 16px;">Apakah anda yakin mengubah status progres cucian?</h3>
        <p style="font-size: 12px; color: #6B7280; margin-top: 6px;">
            Status akan berubah dari <strong id="modalDari"></strong> menjadi <strong id="modalKe"></strong>.
        </p>
        <div style="display: flex; gap: 12px; margin-top: 22px;">
            <button type="button" onclick="tutupModalKonfirmasi()"
                    style="flex: 1; padding: 11px 16px; border-radius: 12px; border: 1px solid #D1D5DB; background-color: #FFFFFF; color: #4B5563; font-size: 13px; font-weight: 500; cursor: pointer;">Batal</button>
            <button type="button" onclick="kirimUbahStatus()"
                    style="flex: 1; padding: 11px 16px; border-radius: 12px; border: none; background-color: #0085FF; color: #FFFFFF; font-size: 13px; font-weight: 600; cursor: pointer;">Ubah</button>
        </div>
    </div>
</div>

<script>
    let formAktif = null;
    function bukaModalKonfirmasi(idForm, dari, ke) {
        formAktif = idForm;
        document.getElementById('modalDari').textContent = dari;
        document.getElementById('modalKe').textContent = ke;
        document.getElementById('modalKonfirmasi').style.display = 'flex';
    }
    function tutupModalKonfirmasi() {
        document.getElementById('modalKonfirmasi').style.display = 'none';
        formAktif = null;
    }
    function kirimUbahStatus() {
        if (formAktif) document.getElementById(formAktif).submit();
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupModalKonfirmasi(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>