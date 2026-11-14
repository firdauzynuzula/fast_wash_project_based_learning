<?php
$title = 'Rincian Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

// Ambil info utama pesanan
$stmtPesanan = $pdo->prepare("
    SELECT p.*,
           pl.nama AS nama_pelanggan,
           pl.no_telepon AS no_pelanggan,
           pl.alamat AS alamat_pelanggan,
           k.nama AS nama_kasir,
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
     WHERE p.id_pesanan = :id
");
$stmtPesanan->execute(['id' => $id]);
$pesanan = $stmtPesanan->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

// Ambil riwayat status untuk audit
$stmtRiwayat = $pdo->prepare("
    SELECT r.*, k.nama AS nama_petugas
      FROM riwayat_status_pesanan r
      LEFT JOIN karyawan k ON k.id_karyawan = r.id_karyawan
     WHERE r.id_pesanan = :id
     ORDER BY r.waktu ASC
");
$stmtRiwayat->execute(['id' => $id]);
$riwayatList = $stmtRiwayat->fetchAll();

// Ambil data transaksi bayar jika ada
$stmtTrx = $pdo->prepare("
    SELECT * FROM riwayat_transaksi
     WHERE id_pesanan = :id AND status_transaksi = 'valid'
     ORDER BY tanggal_bayar DESC LIMIT 1
");
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
        'next_btn' => 'Lanjut ke Diproses',
    ],
    'diproses' => [
        'urutan'   => 2,
        'label'    => 'Diproses',
        'next'     => 'selesai',
        'next_btn' => 'Lanjut ke Selesai',
    ],
    'selesai' => [
        'urutan'   => 3,
        'label'    => 'Selesai',
        'next'     => 'diambil',
        'next_btn' => 'Lanjut ke Diambil',
    ],
    'diambil' => [
        'urutan'   => 4,
        'label'    => 'Diambil',
        'next'     => null,
        'next_btn' => null,
    ],
];

$currentStepNum = $flow[$currentStatus]['urutan'] ?? 1;
$nextStatus     = $flow[$currentStatus]['next'] ?? null;
$nextBtnLabel   = $flow[$currentStatus]['next_btn'] ?? null;

$flashSukses = $_SESSION['flash_sukses'] ?? null;
$flashError  = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_sukses'], $_SESSION['flash_error']);

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'transaksi/status'],
    ['label' => 'Rincian Pesanan', 'url' => ''],
];

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

<!-- Konten Utama Rincian Pesanan (Sesuai UI/UX Designer Figma) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">

    <!-- Kolom Kiri: Ringkasan Layanan & Status Pengerjaan (Lebar 2/3) -->
    <div class="lg:col-span-2 space-y-6">

        <!-- 1. Card Ringkasan Layanan (Lengkap dengan Ilustrasi Mesin Cuci & Tanaman) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xs border border-slate-100 flex items-center justify-between">
            <div class="space-y-2">
                <!-- Pill Status Pesanan -->
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?php
                        echo match ($currentStatus) {
                            'selesai'  => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
                            'diproses' => 'bg-blue-50 text-[#0085ff] border border-blue-200',
                            'diambil'  => 'bg-slate-100 text-slate-700 border border-slate-200',
                            default    => 'bg-amber-50 text-amber-700 border border-amber-200',
                        };
                    ?>">
                        Pesanan <?php echo ucfirst($currentStatus); ?>

                    </span>
                </div>

                <!-- Nama Layanan -->
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">
                    <?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?>

                </h1>

                <!-- Subtitle Kode, Berat, & Tanggal -->
                <p class="text-xs text-slate-400">
                    <?php echo e($pesanan['kode_pesanan']); ?> · <?php echo (float) $pesanan['total_qty']; ?> kg · pesanan dibuat <?php echo date('d F Y', strtotime($pesanan['tanggal_pesan'])); ?>

                </p>
            </div>

            <!-- Ilustrasi Mesin Cuci Khas Fast Wash di Sebelah Kanan -->
            <div class="shrink-0 hidden sm:block w-36 h-28">
                <svg class="w-full h-full" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="80" cy="112" rx="70" ry="6" fill="#eaf3fe"/>
                    <!-- Tanaman Kecil -->
                    <rect x="18" y="92" width="12" height="14" rx="2" fill="#fbbf24"/>
                    <path d="M24 92 C21 84, 14 86, 16 78 C18 85, 23 88, 24 92 Z" fill="#34d399"/>
                    <path d="M24 92 C26 84, 34 86, 32 78 C30 85, 25 88, 24 92 Z" fill="#10b981"/>
                    <!-- Mesin Cuci -->
                    <rect x="48" y="32" width="58" height="74" rx="10" fill="#ffffff" stroke="#93c5fd" stroke-width="3"/>
                    <circle cx="77" cy="74" r="20" fill="#0085ff" stroke="#bae6fd" stroke-width="4"/>
                    <circle cx="77" cy="74" r="12" fill="#0284c7"/>
                    <path d="M71 68 A10 10 0 0 1 85 75" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/>
                    <!-- Keranjang -->
                    <path d="M115 72 L138 72 L134 104 L119 104 Z" fill="#bae6fd" stroke="#38bdf8" stroke-width="1.5"/>
                    <circle cx="122" cy="68" r="5" fill="#fb7185"/>
                    <circle cx="131" cy="67" r="6" fill="#fbbf24"/>
                </svg>
            </div>
        </div>

        <!-- 2. Card Status Pengerjaan (Stepper 4 Node: Diterima - Diproses - Selesai - Diambil) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xs border border-slate-100">
            <div class="flex items-center justify-between pb-5 border-b border-slate-100 mb-8">
                <h2 class="text-base font-bold text-slate-800">Status Pengerjaan</h2>
                <!-- Pill Status Saat Ini di Kanan -->
                <span class="px-4 py-1 rounded-full text-xs font-semibold bg-blue-50 text-[#0085ff] border border-blue-200">
                    <?php echo ucfirst($currentStatus); ?>

                </span>
            </div>

            <!-- Stepper Horizontal Sesuai Desain Figma -->
            <div class="relative px-4 sm:px-8 mb-8">
                <!-- Garis Penghubung Biru Antar Step -->
                <div class="absolute top-5 left-10 right-10 h-1 bg-[#0085ff] z-0"></div>

                <div class="grid grid-cols-4 relative z-10">
                    <?php foreach ($flow as $key => $stInfo): ?>
                        <?php
                        $stepNum = $stInfo['urutan'];
                        $isPast    = ($stepNum < $currentStepNum);
                        $isCurrent = ($stepNum === $currentStepNum);

                        // Ikon per node stepper
                        if ($key === 'diterima') {
                            $nodeIcon = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                        } elseif ($key === 'diproses') {
                            $nodeIcon = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="4"/></svg>';
                        } elseif ($key === 'selesai') {
                            $nodeIcon = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>';
                        } else {
                            $nodeIcon = '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
                        }
                        ?>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs ring-4 ring-white shadow-xs <?php
                                if ($isPast || $isCurrent) {
                                    echo 'bg-[#0085ff] text-white';
                                } else {
                                    echo 'bg-slate-300 text-white';
                                }
                            ?>">
                                <?php echo $nodeIcon; ?>

                            </div>
                            <span class="mt-2.5 text-xs font-semibold <?php echo ($isPast || $isCurrent) ? 'text-slate-800' : 'text-slate-400'; ?>">
                                <?php echo e($stInfo['label']); ?>

                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol Lanjut ke Status Berikutnya -->
            <?php if ($nextStatus): ?>
                <form action="<?php echo $base; ?>transaksi/proses_status" method="post" class="pt-4 border-t border-slate-100 flex justify-end">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="status_tujuan" value="<?php echo e($nextStatus); ?>">
                    <button type="submit"
                            onclick="return confirm('Ubah status pengerjaan pesanan menjadi <?php echo strtoupper($nextStatus); ?>?')"
                            class="px-5 py-2.5 bg-[#0085ff] hover:bg-[#0070d8] text-white font-semibold text-xs rounded-2xl shadow-xs transition-colors flex items-center gap-2">
                        <span><?php echo e($nextBtnLabel); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </form>
            <?php else: ?>
                <div class="pt-4 border-t border-slate-100 text-center text-xs font-semibold text-emerald-600 flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Pesanan telah selesai dan diserahkan ke pelanggan</span>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Kolom Kanan: Detail Informasi Pesanan (Lebar 1/3 Sesuai Figma) -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-xs border border-slate-100 space-y-4">
            <!-- Key-Value Sesuai Figma -->
            <div class="space-y-3.5 text-xs text-slate-600 pb-2">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Pelanggan</span>
                    <span class="font-bold text-slate-800 text-sm"><?php echo e($pesanan['nama_pelanggan']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Layanan</span>
                    <span class="font-semibold text-slate-700"><?php echo e($pesanan['nama_layanan'] ?: 'Cuci + Setrika'); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Berat</span>
                    <span class="font-semibold text-slate-700"><?php echo (float) $pesanan['total_qty']; ?> kg</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Total</span>
                    <span class="font-bold text-slate-800 text-sm">Rp <?php echo number_format((float) $pesanan['total_harga'], 0, ',', '.'); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Pembayaran</span>
                    <span class="font-bold <?php echo $isLunas ? 'text-emerald-600' : 'text-amber-600'; ?>">
                        <?php echo e($metodeBayar); ?> - <?php echo $isLunas ? 'Lunas' : 'Belum Lunas'; ?>

                    </span>
                </div>
            </div>

            <!-- Tombol Konfirmasi Bayar jika belum lunas -->
            <?php if (!$isLunas): ?>
                <form action="<?php echo $base; ?>transaksi/bayar" method="post" class="pt-2">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id_pesanan" value="<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="redirect" value="transaksi/detail?id=<?php echo (int) $pesanan['id_pesanan']; ?>">
                    <input type="hidden" name="metode" value="tunai">
                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-2xl transition-colors">
                        Lunasi Sekarang
                    </button>
                </form>
            <?php endif; ?>

            <!-- Tombol Cetak Nota (Border Biru #0085ff Sesuai Figma) -->
            <div class="pt-2">
                <a href="<?php echo $base; ?>transaksi/nota?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
                   class="w-full py-3 px-4 border-2 border-[#0085ff] text-[#0085ff] hover:bg-blue-50 font-bold text-sm rounded-2xl transition-colors block text-center">
                    Cetak Nota
                </a>
            </div>

            <!-- Link Navigasi Kembali ke Semua Pesanan -->
            <div class="pt-1 text-center">
                <a href="<?php echo $base; ?>transaksi/status" class="text-xs font-normal text-slate-400 hover:text-slate-700 transition-colors">
                    Semua Pesanan
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
