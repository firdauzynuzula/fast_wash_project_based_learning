<?php
$title = 'Pesanan Dibuat';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.id_pesanan,
           p.kode_pesanan,
           p.tanggal_pesan,
           p.estimasi_selesai,
           p.status,
           p.status_bayar,
           p.total_harga,
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
     WHERE p.id_pesanan = :id
");
$stmt->execute(['id' => $id]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'transaksi/status');
    exit;
}

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'transaksi/status'],
    ['label' => 'Pesanan Baru', 'url' => 'transaksi/tambah'],
    ['label' => 'Pesanan Dibuat', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto my-6 sm:my-10">
    <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-xs border border-slate-100 text-center">

        <!-- Ikon Centang Biru Muda (Sesuai Desain Figma) -->
        <div class="w-16 h-16 mx-auto rounded-full bg-[#eaf3fe] text-[#0085ff] flex items-center justify-center mb-5">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <!-- Judul Halaman -->
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Pesanan dibuat</h1>

        <!-- Subtitle Info Pesanan -->
        <p class="text-xs text-slate-400 mt-2">
            <span class="font-semibold text-slate-600"><?php echo e($pesanan['kode_pesanan']); ?></span> for <?php echo e($pesanan['nama_pelanggan']); ?> | <?php echo e($pesanan['nama_layanan'] ?: 'Cuci'); ?> | <?php echo (float) $pesanan['total_qty']; ?> kg | Rp <?php echo number_format((float) $pesanan['total_harga'], 0, ',', '.'); ?>
        </p>

        <!-- Ilustrasi Mesin Cuci & Keranjang Laundry (Sesuai Desain Figma) -->
        <div class="my-8 flex items-center justify-center">
            <div class="relative w-44 h-36 flex items-center justify-center">
                <!-- Ilustrasi SVG Mesin Cuci, Keranjang & Tanaman -->
                <svg class="w-full h-full" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Meja / Lantai -->
                    <ellipse cx="100" cy="148" rx="85" ry="8" fill="#eaf3fe"/>

                    <!-- Tanaman Kecil di Kiri -->
                    <rect x="25" y="125" width="16" height="18" rx="3" fill="#fbbf24"/>
                    <path d="M33 125 C30 115, 20 118, 22 108 C25 116, 32 120, 33 125 Z" fill="#34d399"/>
                    <path d="M33 125 C36 115, 46 118, 44 108 C41 116, 34 120, 33 125 Z" fill="#10b981"/>

                    <!-- Mesin Cuci Utama (Tengah) -->
                    <rect x="62" y="50" width="76" height="92" rx="14" fill="#ffffff" stroke="#93c5fd" stroke-width="4"/>
                    <!-- Panel Atas Mesin Cuci -->
                    <rect x="70" y="58" width="60" height="10" rx="3" fill="#f1f5f9"/>
                    <circle cx="118" cy="63" r="2.5" fill="#38bdf8"/>
                    <circle cx="124" cy="63" r="2.5" fill="#0085ff"/>
                    <!-- Pintu Kaca Mesin Cuci -->
                    <circle cx="100" cy="102" r="26" fill="#0085ff" stroke="#bae6fd" stroke-width="5"/>
                    <circle cx="100" cy="102" r="16" fill="#0284c7"/>
                    <path d="M92 94 A14 14 0 0 1 112 104" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>

                    <!-- Keranjang Laundry di Kanan -->
                    <path d="M148 100 L176 100 L171 140 L153 140 Z" fill="#bae6fd" stroke="#38bdf8" stroke-width="2"/>
                    <line x1="152" y1="108" x2="155" y2="135" stroke="#0085ff" stroke-width="1.5"/>
                    <line x1="162" y1="108" x2="162" y2="135" stroke="#0085ff" stroke-width="1.5"/>
                    <line x1="172" y1="108" x2="169" y2="135" stroke="#0085ff" stroke-width="1.5"/>
                    <!-- Pakaian Menumpuk di Keranjang -->
                    <circle cx="156" cy="95" r="7" fill="#fb7185"/>
                    <circle cx="168" cy="94" r="8" fill="#fbbf24"/>
                    <circle cx="162" cy="88" r="7" fill="#818cf8"/>
                </svg>
            </div>
        </div>

        <!-- 2 Tombol Aksi Sesuai Figma -->
        <div class="space-y-3">
            <!-- Tombol 1: Lihat Pesanan (Solid Biru #0085ff) -->
            <a href="<?php echo $base; ?>transaksi/detail?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
               class="w-full py-3.5 px-4 bg-[#0085ff] hover:bg-[#0070d8] text-white font-bold text-sm rounded-2xl shadow-xs transition-colors block text-center">
                Lihat Pesanan
            </a>

            <!-- Tombol 2: Buat Pesanan Baru (Biru Muda #eaf3fe) -->
            <a href="<?php echo $base; ?>transaksi/tambah"
               class="w-full py-3.5 px-4 bg-[#eaf3fe] hover:bg-blue-100 text-[#0085ff] font-bold text-sm rounded-2xl transition-colors block text-center">
                Buat Pesanan Baru
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
