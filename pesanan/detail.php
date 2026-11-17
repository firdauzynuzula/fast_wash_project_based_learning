<?php
$title = 'Rincian Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Info utama pesanan
$stmtPesanan = $pdo->prepare(
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
      WHERE p.id_pesanan = :id"
);
$stmtPesanan->execute(['id' => $id]);
$pesanan = $stmtPesanan->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Transaksi pembayaran (jika ada)
$stmtTrx = $pdo->prepare(
    "SELECT * FROM riwayat_transaksi
      WHERE id_pesanan = :id AND status_transaksi = 'valid'
      ORDER BY tanggal_bayar DESC LIMIT 1"
);
$stmtTrx->execute(['id' => $id]);
$trxBayar = $stmtTrx->fetch();

$currentStatus = strtolower($pesanan['status']);
$isLunas       = ($pesanan['status_bayar'] === 'lunas');

$labelMetode = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];
$metodeKey   = $trxBayar ? strtolower($trxBayar['jenis_pembayaran']) : '';
$metodeText  = $labelMetode[$metodeKey] ?? '';
if ($isLunas) {
    $teksBayar = ($metodeText !== '' ? $metodeText . ' – ' : '') . 'Lunas';
} else {
    $teksBayar = 'Belum Lunas';
}

// Alur status
$urutan = ['diterima' => 1, 'diproses' => 2, 'selesai' => 3, 'diambil' => 4];
$nextMap = ['diterima' => 'diproses', 'diproses' => 'selesai', 'selesai' => 'diambil', 'diambil' => null];
$prevMap = ['diterima' => null, 'diproses' => 'diterima', 'selesai' => 'diproses', 'diambil' => 'selesai'];

$currentStepNum = $urutan[$currentStatus] ?? 1;
$nextStatus     = $nextMap[$currentStatus] ?? null;
$previousStatus = $prevMap[$currentStatus] ?? null;

// Tanggal format Indonesia
$bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$ts = strtotime($pesanan['tanggal_pesan']);
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

require_once __DIR__ . '/../includes/header.php';
?>

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
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <button type="submit" style="color: #16A34A; font-weight: 700; font-size: 12px; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; padding: 4px;">Undo</button>
                </form>
            <?php endif; ?>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="color: #65A30D; font-weight: 700; cursor: pointer; padding: 4px;">&times;</button>
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
        <button type="button" onclick="this.parentElement.remove()" style="color: #CA8A04; font-weight: 700; cursor: pointer; padding: 4px;">&times;</button>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2.08fr 1fr; gap: 24px; align-items: start;">

    <!-- Kolom kiri -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- Card ringkasan layanan -->
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; flex-direction: column; align-items: flex-start;">
                <?php if ($currentStatus === 'diambil'): ?>
                    <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; background-color: #D1FAE5; color: #047857;">Pesanan Selesai</span>
                <?php else: ?>
                    <span style="display: inline-block; padding: 4px 14px; border-radius: 9999px; font-size: 11px; font-weight: 600; background-color: #FEF3C7; color: #D97706;">Pending</span>
                <?php endif; ?>

                <h1 style="font-size: 20px; font-weight: 600; color: #111827; line-height: 1.3; margin-top: 10px;">
                    <?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?>
                </h1>
                <p style="font-size: 12px; color: #9CA3AF; margin-top: 4px;">
                    <?php echo e($pesanan['kode_pesanan']); ?> · <?php echo (float) $pesanan['total_qty']; ?> kg · pesanan dibuat <?php echo $tanggalPesan; ?>
                </p>
            </div>

            <div style="width: 110px; height: 64px; flex-shrink: 0;">
                <svg width="110" height="64" viewBox="0 0 160 92" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block;">
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
                </svg>
            </div>
        </div>

        <!-- Card status pengerjaan -->
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 16px; font-weight: 600; color: #111827;">Status Pengerjaan</h2>
                <span style="padding: 5px 14px; border-radius: 9999px; font-size: 11px; font-weight: 500; background-color: #E0F2FE; color: #0085FF;">
                    <?php echo ucfirst($currentStatus); ?>
                </span>
            </div>

            <!-- Stepper 4 node -->
            <div style="display: flex; margin: 30px 0 32px;">
                <?php
                $stepNodes = [
                    ['label' => 'Diterima', 'icon' => 'check'],
                    ['label' => 'Diproses', 'icon' => 'mesin'],
                    ['label' => 'Selesai',  'icon' => 'keranjang'],
                    ['label' => 'Diambil',  'icon' => 'kotak'],
                ];
                foreach ($stepNodes as $idx => $node):
                    $stepNum = $idx + 1;
                    $isDone  = ($stepNum <= $currentStepNum);
                    $bgCircle = $isDone ? '#0085FF' : '#E8EEF5';
                    $warnaIkon = $isDone ? '#FFFFFF' : '#9CA3AF';
                    $warnaLabel = $isDone ? '#111827' : '#6B7280';
                    ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;">
                        <?php if ($idx > 0): ?>
                            <div style="position: absolute; top: 13px; right: 50%; width: 100%; height: 2px; background-color: <?php echo $isDone ? '#0085FF' : '#E5E7EB'; ?>; z-index: 0;"></div>
                        <?php endif; ?>
                        <div style="position: relative; z-index: 1; width: 28px; height: 28px; border-radius: 50%; background-color: <?php echo $bgCircle; ?>; display: flex; align-items: center; justify-content: center;">
                            <?php echo ikonStep($node['icon'], $warnaIkon); ?>
                        </div>
                        <span style="margin-top: 8px; font-size: 12px; font-weight: 400; color: <?php echo $warnaLabel; ?>;">
                            <?php echo $node['label']; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tombol ubah status (membuka popup konfirmasi) -->
            <?php if ($nextStatus): ?>
                <form id="formUbahStatus" action="<?php echo $base; ?>pesanan/proses_edit" method="post">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="status_tujuan" value="<?php echo e($nextStatus); ?>">
                    <button type="button" onclick="bukaModalKonfirmasi()"
                            style="display: block; width: 100%; padding: 12px 20px; border-radius: 12px; border: none; background-color: #0085FF; color: #FFFFFF; font-size: 14px; font-weight: 500; text-align: center; cursor: pointer;">
                        Ubah Ke <?php echo ucfirst($nextStatus); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom kanan -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 22px 22px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #4B5563;">Pelanggan</span>
                    <span style="font-weight: 600; color: #111827; font-size: 12px;"><?php echo e($pesanan['nama_pelanggan']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #4B5563;">Layanan</span>
                    <span style="font-weight: 600; color: #111827; font-size: 12px;"><?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #4B5563;">Berat</span>
                    <span style="font-weight: 600; color: #111827; font-size: 12px;"><?php echo (float) $pesanan['total_qty']; ?> kg</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #4B5563;">Total</span>
                    <span style="font-weight: 600; color: #111827; font-size: 12px;">Rp <?php echo number_format((float) $pesanan['total_harga'], 0, '.', ','); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #4B5563;">Pembayaran</span>
                    <span style="font-weight: 600; color: #111827; font-size: 12px;"><?php echo e($teksBayar); ?></span>
                </div>
            </div>

            <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
               style="display: block; margin-top: 22px; padding: 11px 16px; border-radius: 12px; border: 1px solid #0085FF; color: #0085FF; font-weight: 600; font-size: 12px; text-align: center; background-color: #FFFFFF;">
                Cetak Nota
            </a>

            <div style="margin-top: 14px; text-align: center;">
                <a href="<?php echo $base; ?>pesanan" style="font-size: 12px; color: #6B7280; font-weight: 500; text-decoration: none;">Semua Pesanan</a>
            </div>
        </div>
    </div>
</div>

<!-- Popup konfirmasi ubah status -->
<?php if ($nextStatus): ?>
<div id="modalKonfirmasi" onclick="if (event.target === this) tutupModalKonfirmasi()"
     style="display: none; position: fixed; inset: 0; z-index: 50; background-color: rgba(15,23,42,0.4); align-items: center; justify-content: center; padding: 16px;">
    <div style="background-color: #FFFFFF; border-radius: 20px; width: 100%; max-width: 380px; padding: 28px 24px 24px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
        <div style="width: 48px; height: 48px; margin: 0 auto; border-radius: 50%; background-color: #E0F2FE; display: flex; align-items: center; justify-content: center;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#0085FF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin-top: 16px;">Apakah anda yakin mengubah status progres cucian?</h3>
        <p style="font-size: 12px; color: #6B7280; margin-top: 6px;">
            Status akan berubah dari <strong><?php echo ucfirst($currentStatus); ?></strong> menjadi <strong><?php echo ucfirst($nextStatus); ?></strong>.
        </p>
        <div style="display: flex; gap: 12px; margin-top: 22px;">
            <button type="button" onclick="tutupModalKonfirmasi()"
                    style="flex: 1; padding: 11px 16px; border-radius: 12px; border: 1px solid #D1D5DB; background-color: #FFFFFF; color: #4B5563; font-size: 13px; font-weight: 500; cursor: pointer;">Batal</button>
            <button type="button" onclick="document.getElementById('formUbahStatus').submit()"
                    style="flex: 1; padding: 11px 16px; border-radius: 12px; border: none; background-color: #0085FF; color: #FFFFFF; font-size: 13px; font-weight: 600; cursor: pointer;">Ubah</button>
        </div>
    </div>
</div>

<script>
    function bukaModalKonfirmasi() { document.getElementById('modalKonfirmasi').style.display = 'flex'; }
    function tutupModalKonfirmasi() { document.getElementById('modalKonfirmasi').style.display = 'none'; }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupModalKonfirmasi(); });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
