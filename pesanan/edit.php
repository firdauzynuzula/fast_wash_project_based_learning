<?php
$title = 'Rincian Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Ambil info utama pesanan
$stmtPesanan = $pdo->prepare(
    "SELECT p.*,
            pl.nama AS nama_pelanggan,
            pl.no_telepon AS no_pelanggan,
            pl.alamat AS alamat_pelanggan,
            k.nama AS nama_karyawan,
            (SELECT string_agg(l.nama_layanan, ', ')
               FROM detail_pesanan d
               JOIN layanan l ON l.id_layanan = d.id_layanan
              WHERE d.id_pesanan = p.id_pesanan) AS nama_layanan,
            (SELECT COALESCE(SUM(d.qty), 0)
               FROM detail_pesanan d
              WHERE d.id_pesanan = p.id_pesanan) AS total_qty
       FROM pesanan p
       JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
       LEFT JOIN karyawan k ON k.id_karyawan = p.id_karyawan
      WHERE p.id_pesanan = :id"
);
$stmtPesanan->execute(['id' => $id]);
$pesanan = $stmtPesanan->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Ambil riwayat status untuk audit
$stmtRiwayat = $pdo->prepare(
    "SELECT r.*, k.nama AS nama_petugas
       FROM riwayat_status_pesanan r
       LEFT JOIN karyawan k ON k.id_karyawan = r.id_karyawan
      WHERE r.id_pesanan = :id
      ORDER BY r.waktu ASC"
);
$stmtRiwayat->execute(['id' => $id]);
$riwayatList = $stmtRiwayat->fetchAll();

// Ambil data transaksi bayar jika ada
$stmtTrx = $pdo->prepare(
    "SELECT * FROM riwayat_transaksi
      WHERE id_pesanan = :id AND status_transaksi = 'valid'
      ORDER BY tanggal_bayar DESC LIMIT 1"
);
$stmtTrx->execute(['id' => $id]);
$trxBayar = $stmtTrx->fetch();

$currentStatus = strtolower($pesanan['status']);
$isLunas       = ($pesanan['status_bayar'] === 'lunas');
$metodeBayar   = $trxBayar ? strtoupper($trxBayar['jenis_pembayaran']) : 'TUNAI';

// Alur 4 Tahapan Utama (diterima - diproses - selesai - diambil)
$flow = [
    'diterima' => [
        'urutan'   => 1,
        'label'    => 'Diterima',
        'next'     => 'diproses',
        'next_btn' => 'Ubah Ke Diproses',
    ],
    'diproses' => [
        'urutan'   => 2,
        'label'    => 'Diproses',
        'next'     => 'selesai',
        'next_btn' => 'Ubah Ke Selesai',
    ],
    'selesai' => [
        'urutan'   => 3,
        'label'    => 'Selesai',
        'next'     => 'diambil',
        'next_btn' => 'Ubah Ke Diambil',
    ],
    'diambil' => [
        'urutan'   => 4,
        'label'    => 'Diambil',
        'next'     => null,
        'next_btn' => null,
    ],
];

// Tentukan step yang sudah ditempuh berdasarkan status
$currentStep = 1; // default: Diterima
switch ($currentStatus) {
    case 'diproses':
        $currentStep = 2;
        break;
    case 'selesai':
        $currentStep = 3;
        break;
    case 'diambil':
        $currentStep = 4;
        break;
    default:
        $currentStep = 1;
        break;
}

$currentStepInfo = $flow[$currentStatus] ?? $flow['diterima'];
$currentStepNum  = $currentStepInfo['urutan'] ?? 1;
$nextStatus      = $currentStepInfo['next'] ?? null;
$nextBtnLabel    = $currentStepInfo['next_btn'] ?? null;

$flashSukses = $_SESSION['flash_sukses'] ?? null;
$flashError  = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Rincian Pesanan', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Alert Notifikasi Flash -->
<?php if ($flashSukses): ?>
    <div style="margin-bottom: 20px; padding: 16px; background-color: #ECFDF5; border: 1px solid #BBF7D0; color: #166534; font-size: 14px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 20 20" style="flex-shrink: 0;">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" fill="#166534"/>
            </svg>
            <span style="font-weight: 500;"><?php echo e($flashSukses); ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <?php
            $previousStatus = null;
            switch ($currentStatus) {
                case 'diproses':
                    $previousStatus = 'diterima';
                    break;
                case 'selesai':
                    $previousStatus = 'diproses';
                    break;
                case 'diambil':
                    $previousStatus = 'selesai';
                    break;
                default:
                    $previousStatus = null;
            }
            ?>
            <?php if ($previousStatus): ?>
                <form action="<?php echo $base; ?>pesanan/proses_undo" method="post" style="display: flex; align-items: center; gap: 6px;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <button type="submit"
                            style="color: #16A34A; font-weight: 700; font-size: 12px; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; padding: 4px;">
                        Undo
                    </button>
                </form>
            <?php endif; ?>
            <button type="button" onclick="this.parentElement.remove()" style="color: #65A30D; font-weight: 700; cursor: pointer; padding: 4px;">&times;</button>
        </div>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div style="margin-bottom: 20px; padding: 16px; background-color: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; font-size: 14px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="20" height="20" viewBox="0 0 20 20" style="flex-shrink: 0;">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" fill="#92400E"/>
            </svg>
            <span style="font-weight: 500;"><?php echo e($flashError); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="color: #CA8A04; font-weight: 700; cursor: pointer; padding: 4px;">&times;</button>
    </div>
<?php endif; ?>

<!-- Konten Utama Rincian Pesanan (Layout 2 Kolom - inline CSS) -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- Kolom Kiri: Ringkasan Layanan & Status Pengerjaan (Lebar 2/3) -->
    <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- 1. Card Ringkasan Layanan -->
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Badge Statis -->
                <?php if ($currentStatus === 'diambil'): ?>
                    <span style="display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; background-color: #ECFDF5; color: #15803D; border: 1px solid #BBF7D0;">
                        Pesanan Selesai
                    </span>
                <?php else: ?>
                    <span style="display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;">
                        Proses Berjalan
                    </span>
                <?php endif; ?>

                <!-- Nama Layanan -->
                <h1 style="font-size: 20px; font-weight: 700; color: #1F2937; line-height: 1.3; margin-top: 8px;">
                    <?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?>
                </h1>

                <!-- Subtitle Kode, Berat, & Tanggal -->
                <p style="font-size: 12px; color: #9CA3AF; margin-top: 4px;">
                    <?php echo e($pesanan['kode_pesanan']); ?> · <?php echo (float) $pesanan['total_qty']; ?> kg · pesanan dibuat <?php echo date('d F Y', strtotime($pesanan['tanggal_pesan'])); ?>
                </p>
            </div>

            <!-- Ilustrasi Mesin Cuci & Keranjang (Right) -->
            <div style="width: 96px; height: 96px; flex-shrink: 0;">
                <svg width="96" height="96" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block;">
                    <rect x="8" y="10" width="144" height="100" rx="10" fill="#F0F9FF" stroke="#93C5FD" stroke-width="2"/>
                    <rect x="8" y="10" width="144" height="12" rx="3" fill="#EFF6FF"/>
                    <rect x="20" y="26" width="120" height="44" rx="6" fill="#FFFFFF" stroke="#93C5FD" stroke-width="2"/>
                    <circle cx="80" cy="48" r="18" fill="#0085FF" stroke="#BBF7D0" stroke-width="4"/>
                    <circle cx="80" cy="48" r="10" fill="#2563EB"/>
                    <circle cx="80" cy="48" r="3" fill="#FFFFFF"/>
                    <circle cx="80" cy="32" r="8" fill="#FFFFFF" stroke="#93C5FD" stroke-width="2"/>
                    <rect x="100" y="48" width="52" height="40" rx="6" fill="#FFFFFF" stroke="#38BDF8" stroke-width="2"/>
                    <circle cx="126" cy="56" r="6" fill="#F472B6"/>
                    <circle cx="126" cy="56" r="2" fill="#FFFFFF"/>
                    <circle cx="134" cy="54" r="5" fill="#F59E0B"/>
                    <circle cx="134" cy="54" r="2" fill="#FFFFFF"/>
                </svg>
            </div>
        </div>

        <!-- 2. Card Status Pengerjaan -->
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 20px; border-bottom: 1px solid #F3F4F6; margin-bottom: 24px;">
                <h2 style="font-size: 18px; font-weight: 700; color: #1F2937;">Status Pengerjaan</h2>
                <span style="padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700; background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;">
                    <?php echo ucfirst($currentStatus); ?>
                </span>
            </div>

            <!-- Stepper Horizontal 4 Node -->
            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin: 28px 0;">
                <?php
                // Definisikan 4 tahapan status
                $stepNodes = [
                    ['label' => 'Diterima', 'icon' => 'check'],
                    ['label' => 'Diproses', 'icon' => 'camera'],
                    ['label' => 'Selesai', 'icon' => 'box'],
                    ['label' => 'Diambil', 'icon' => 'cube'],
                ];
                foreach ($stepNodes as $idx => $node): ?>
                    <?php
                    $stepNum = $idx + 1;
                    $isPast    = ($stepNum < $currentStepNum);
                    $isCurrent = ($stepNum === $currentStepNum);
                    $isDone    = $isPast || $isCurrent;

                    // Circle styling - inline style only
                    if ($isCurrent || $isPast) {
                        $circleStyle = 'width: 40px; height: 40px; border-radius: 50%; background-color: #0088FF; color: #FFFFFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 3px solid #0088FF;';
                        $textStyle   = 'margin-top: 8px; font-size: 13px; font-weight: 600; color: #1F2937;';
                    } else {
                        $circleStyle = 'width: 40px; height: 40px; border-radius: 50%; background-color: #FFFFFF; color: #9CA3AF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid #D1D5DB;';
                        $textStyle   = 'margin-top: 8px; font-size: 13px; color: #9CA3AF;';
                    }
                    ?>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="<?php echo $circleStyle; ?>">
                            <?php if ($node['icon'] === 'check'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" style="display: block;">
                                    <path d="M5 13l4 4L19 7" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            <?php elseif ($node['icon'] === 'camera'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" style="display: block;">
                                    <rect x="4" y="2" width="16" height="20" rx="2" stroke="#9CA3AF" stroke-width="2.5"/>
                                    <circle cx="12" cy="13" r="4" stroke="#9CA3AF" stroke-width="2.5"/>
                                </svg>
                            <?php elseif ($node['icon'] === 'box'): ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" style="display: block;">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="#9CA3AF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" style="display: block;">
                                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="#9CA3AF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <span style="<?php echo $textStyle; ?>">
                            <?php echo $node['label']; ?>
                        </span>
                    </div>

                    <!-- Connector line (between steps) -->
                    <?php if ($idx < count($stepNodes) - 1): ?>
                        <div style="flex: 1; height: 2px; margin-top: 19px; background-color: <?php echo $isDone ? '#0088FF' : '#E5E7EB'; ?>; min-width: 24px;"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
                <?php
                // Tentukan status sebelumnya
                $previousStatus = null;
                switch ($currentStatus) {
                    case 'diproses':
                        $previousStatus = 'diterima';
                        break;
                    case 'selesai':
                        $previousStatus = 'diproses';
                        break;
                    case 'diambil':
                        $previousStatus = 'selesai';
                        break;
                    default:
                        $previousStatus = null;
                }
                ?>

                <?php if ($currentStatus === 'diterima'): ?>
                    <!-- Status Diterima: tombol biru penuh -->
                    <form action="<?php echo $base; ?>pesanan/proses_edit" method="post">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                        <input type="hidden" name="status_tujuan" value="diproses">
                        <button type="submit"
                                style="flex: 1; padding: 12px 20px; border-radius: 12px; border: none; background-color: #0088FF; color: #FFFFFF; font-size: 14px; font-weight: 600; text-align: center; cursor: pointer; transition: background-color 0.2s;">
                            Ubah Ke Diproses
                        </button>
                    </form>

                <?php elseif ($currentStatus === 'diambil'): ?>
                    <!-- Status Diambil: tombol kembali + pesan selesai -->
                    <?php if ($previousStatus): ?>
                        <form action="<?php echo $base; ?>pesanan/proses_undo" method="post" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                            <button type="submit"
                                    onclick="return confirm('Kembalikan status pesanan ke tahap sebelumnya?')"
                                    style="padding: 12px 18px; border-radius: 12px; border: 1px solid #D1D5DB; background-color: #FFFFFF; color: #4B5563; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap; transition: background-color 0.2s;">
                                <span style="color: #6B7280;">←</span>
                                <span>Kembali ke <?php echo ucfirst($previousStatus); ?></span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div style="display: flex; align-items: center; gap: 8px; color: #15803D; font-weight: 600;">
                        <svg width="16" height="16" viewBox="0 0 24 24" style="flex-shrink: 0;">
                            <path d="M5 13l4 4L19 7" stroke="#15803D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>✓ Pesanan telah selesai dan diserahkan ke pelanggan</span>
                    </div>

                <?php else: ?>
                    <!-- Status Diproses/Selesai: Undo + tombol utama -->
                    <?php if ($previousStatus): ?>
                        <form action="<?php echo $base; ?>pesanan/proses_undo" method="post" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                            <button type="submit"
                                    onclick="return confirm('Kembalikan status pesanan ke tahap sebelumnya?')"
                                    style="padding: 12px 18px; border-radius: 12px; border: 1px solid #D1D5DB; background-color: #FFFFFF; color: #4B5563; font-size: 14px; font-weight: 500; cursor: pointer; white-space: nowrap; transition: background-color 0.2s;">
                                <span style="color: #6B7280;">←</span>
                                <span>Kembali ke <?php echo ucfirst($previousStatus); ?></span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($nextStatus): ?>
                        <form action="<?php echo $base; ?>pesanan/proses_edit" method="post" style="flex: 1;">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                            <input type="hidden" name="status_tujuan" value="<?php echo e($nextStatus); ?>">
                            <button type="submit"
                                    onclick="return confirm('Ubah status pengerjaan pesanan menjadi <?php echo strtoupper($nextStatus); ?>?')"
                                    style="padding: 12px 20px; border-radius: 12px; border: none; background-color: #0088FF; color: #FFFFFF; font-size: 14px; font-weight: 600; text-align: center; cursor: pointer; transition: background-color 0.2s;">
                                Ubah Ke <?php echo ucfirst($nextStatus); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Kolom Kanan: Ringkasan Pesanan -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div style="background-color: #FFFFFF; border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #9CA3AF;">Pelanggan</span>
                    <span style="font-weight: 700; color: #1F2937; font-size: 13px;"><?php echo e($pesanan['nama_pelanggan']); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #9CA3AF;">Layanan</span>
                    <span style="font-weight: 600; color: #374151; font-size: 13px;"><?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #9CA3AF;">Berat</span>
                    <span style="font-weight: 600; color: #374151; font-size: 13px;"><?php echo (float) $pesanan['total_qty']; ?> kg</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #9CA3AF;">Total</span>
                    <span style="font-weight: 700; color: #1F2937; font-size: 13px;">Rp <?php echo number_format((float) $pesanan['total_harga'], 0, ',', '.'); ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 12px; color: #9CA3AF;">Pembayaran</span>
                    <span style="font-weight: 700; <?php echo $isLunas ? 'color: #15803D' : 'color: #F59E0B'; ?>; font-size: 13px;">
                        <?php echo e($metodeBayar); ?> - <?php echo $isLunas ? 'Lunas' : 'Belum Lunas'; ?>
                    </span>
                </div>
            </div>

            <?php if (!$isLunas): ?>
                <form action="<?php echo $base; ?>transaksi/bayar" method="post" style="margin-top: 16px;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="redirect" value="pesanan/detail?id=<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="metode" value="tunai">
                    <button type="submit"
                            style="width: 100%; padding: 12px 20px; border-radius: 12px; background-color: #10B981; color: #FFFFFF; font-weight: 700; font-size: 13px; cursor: pointer; transition: background-color 0.2s;">
                        Lunasi Sekarang
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 16px;">
                <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
                   style="display: block; width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #0088FF; color: #0088FF; font-weight: 700; font-size: 13px; text-align: center; cursor: pointer; transition: background-color 0.2s;">
                    Cetak Nota
                </a>
            </div>

            <div style="margin-top: 8px; text-align: center;">
                <a href="<?php echo $base; ?>pesanan" style="font-size: 12px; color: #9CA3AF; font-weight: 400; text-decoration: none; transition: color 0.2s;">
                    Semua Pesanan
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
