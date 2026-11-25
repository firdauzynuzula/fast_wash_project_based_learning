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
              WHERE d.id_pesanan = p.id_pesanan) AS total_qty,
            (SELECT COALESCE(SUM(t.jumlah_bayar), 0)
                FROM riwayat_transaksi t
               WHERE t.id_pesanan = p.id_pesanan
                 AND t.status_transaksi = 'valid') AS total_dibayar
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
$semuaSelesai = true;
$totalBerat  = 0.0;
$totalHarga  = 0.0;
$totalTagihan = 0.0;
$namaLayanan = [];
foreach ($daftarPesanan as $ps) {
    if ($ps['status_bayar'] !== 'lunas') {
        $semuaLunas = false;
    }
    if ($ps['status'] !== 'selesai') {
        $semuaSelesai = false;
    }
    $totalBerat += (float) $ps['total_qty'];
    $totalHarga += (float) $ps['total_harga'];
    $totalTagihan += max(0, (float) $ps['total_harga'] - (float) $ps['total_dibayar']);
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
$flashPopup  = $_SESSION['flash_popup'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error'], $_SESSION['flash_popup']);

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
    .dt-bayar { display: block; width: 100%; margin-top: 22px; padding: 12px 16px; border: 0; border-radius: 12px; color: #fff; background: #0085FF; font: inherit; font-size: 13px; font-weight: 600; text-align: center; cursor: pointer; }
    .dt-bayar:hover { background: #0070D8; }
    .dt-modal-overlay { display: none; position: fixed; inset: 0; z-index: 70; align-items: center; justify-content: center; padding: 16px; background: rgba(15,23,42,.48); backdrop-filter: blur(4px); }
    .dt-modal-overlay.show { display: flex; }
    .dt-modal-card { width: 100%; max-width: 440px; padding: 28px; box-sizing: border-box; border-radius: 24px; background: #fff; box-shadow: 0 24px 60px rgba(15,23,42,.24); }
    .dt-modal-icon { display: flex; width: 54px; height: 54px; margin: 0 auto 15px; align-items: center; justify-content: center; border-radius: 50%; color: #0085FF; background: #EAF5FF; }
    .dt-modal-title { margin: 0; color: #0A2A5E; font-size: 19px; font-weight: 700; text-align: center; }
    .dt-modal-desc { margin: 8px 0 20px; color: #64748B; font-size: 13px; line-height: 1.6; text-align: center; }
    .dt-methods { display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px; margin-bottom: 22px; }
    .dt-method { position: relative; cursor: pointer; }
    .dt-method input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .dt-method span { display: block; padding: 11px 5px; border: 1px solid #E2E8F0; border-radius: 12px; color: #475569; background: #F8FAFC; font-size: 12px; font-weight: 600; text-align: center; }
    .dt-method input:checked + span { border-color: #0085FF; color: #0085FF; background: #EFF6FF; }
    .dt-method input:focus-visible + span { outline: 3px solid rgba(0,133,255,.25); }
    .dt-modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
    .dt-modal-btn { min-width: 112px; padding: 11px 16px; border: 1px solid #E2E8F0; border-radius: 12px; color: #475569; background: #fff; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
    .dt-modal-btn.primary { border-color: #0085FF; color: #fff; background: #0085FF; }
    .dt-modal-btn.primary:hover { background: #0070D8; }
    .dt-alert-card { max-width: 390px; text-align: center; }
    .dt-alert-card .dt-modal-desc { margin-bottom: 22px; }
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
                                    onclick="<?php echo $nextSt === 'diambil' && !$semuaLunas
                                        ? "tampilkanPeringatanStatus('Pesanan belum dibayar, tidak dapat diambil.')"
                                        : "bukaModalKonfirmasi('formUbah{$idPs}', '" . e(ucfirst($st)) . "', '" . e(ucfirst($nextSt)) . "')"; ?>">
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

            <?php if (!$semuaLunas && $semuaSelesai): ?>
                <button type="button" class="dt-bayar" onclick="bukaModalPembayaran()">Bayar</button>
            <?php endif; ?>
            <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $pesananPertama['id_pesanan']; ?>" class="dt-nota">Cetak Nota</a>
            <a href="<?php echo $base; ?>pesanan" class="dt-semua">Semua Pesanan</a>
        </div>
    </div>
</div>

<div id="modalPembayaranBatch" class="dt-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="judulPembayaranBatch" onclick="if (event.target === this) tutupModalPembayaran()">
    <form action="<?php echo $base; ?>transaksi/bayar" method="post" class="dt-modal-card">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesananPertama['id_pesanan']; ?>">
        <input type="hidden" name="bayar_batch" value="1">
        <input type="hidden" name="redirect" value="pesanan/detail?id=<?php echo (int) $pesananPertama['id_pesanan']; ?>">
        <div class="dt-modal-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18m-14 5h4"/></svg>
        </div>
        <h2 id="judulPembayaranBatch" class="dt-modal-title">Konfirmasi Pembayaran</h2>
        <p class="dt-modal-desc">Pembayaran mencakup semua pesanan yang sudah selesai dalam transaksi ini.</p>
        <div class="dt-total" style="margin:0 0 20px;padding:14px 0;border-top:1px solid #E2E8F0;border-bottom:1px solid #E2E8F0;">
            <span class="k">Total tagihan</span>
            <span class="v">Rp <?php echo $rpEn($totalTagihan); ?></span>
        </div>
        <p style="margin:0 0 10px;color:#334155;font-size:13px;font-weight:600;">Pilih metode pembayaran</p>
        <div class="dt-methods">
            <?php foreach (['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'] as $metode => $label): ?>
                <label class="dt-method">
                    <input type="radio" name="metode" value="<?php echo $metode; ?>" required>
                    <span><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="dt-modal-actions">
            <button type="button" class="dt-modal-btn" onclick="tutupModalPembayaran()">Batal</button>
            <button type="submit" class="dt-modal-btn primary">Konfirmasi Bayar</button>
        </div>
    </form>
</div>

<div id="modalPeringatanStatus" class="dt-modal-overlay" role="alertdialog" aria-modal="true" aria-labelledby="judulPeringatanStatus" aria-describedby="isiPeringatanStatus" onclick="if (event.target === this) tutupModalPeringatanStatus()">
    <div class="dt-modal-card dt-alert-card">
        <div class="dt-modal-icon" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/></svg>
        </div>
        <h2 id="judulPeringatanStatus" class="dt-modal-title">Pesanan Belum Bisa Diambil</h2>
        <p id="isiPeringatanStatus" class="dt-modal-desc"></p>
        <div class="dt-modal-actions" style="justify-content:center;">
            <button type="button" class="dt-modal-btn primary" onclick="tutupModalPeringatanStatus()">Mengerti</button>
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
    function bukaModalPembayaran() {
        document.getElementById('modalPembayaranBatch').classList.add('show');
    }
    function tutupModalPembayaran() {
        document.getElementById('modalPembayaranBatch').classList.remove('show');
    }
    function tampilkanPeringatanStatus(pesan) {
        document.getElementById('isiPeringatanStatus').textContent = pesan;
        document.getElementById('modalPeringatanStatus').classList.add('show');
    }
    function tutupModalPeringatanStatus() {
        document.getElementById('modalPeringatanStatus').classList.remove('show');
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            tutupModalKonfirmasi();
            tutupModalPembayaran();
            tutupModalPeringatanStatus();
        }
    });
    <?php if ($flashPopup): ?>
        tampilkanPeringatanStatus(<?php echo json_encode($flashPopup); ?>);
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>