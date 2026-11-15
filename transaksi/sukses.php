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

<div style="max-width: 500px; margin: 24px auto;">
    <div style="max-width: 460px; width: 100%; margin: 0 auto; padding: 24px 32px; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); background-color: #FFFFFF; text-align: center;">

        <!-- Ikon Centang Biru Muda (Sesuai Desain Figma) -->
        <div style="width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 50%; background-color: #EAF3FE; color: #0085FF; display: flex; align-items: center; justify-content: center;">
            <svg width="28" height="28" viewBox="0 0 24 24" style="display: block;">
                <path d="M5 13l4 4L19 7" stroke="#0085FF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <!-- Judul Halaman -->
        <h1 style="font-size: 24px; font-weight: 700; color: #1F2937; line-height: 1.3; margin-bottom: 8px;">
            Pesanan dibuat
        </h1>

        <!-- Subtitle Info Pesanan -->
        <p style="font-size: 13px; color: #9CA3AF; margin-top: 4px;">
            <span style="font-weight: 700; color: #374151;"><?php echo e($pesanan['kode_pesanan']); ?></span> for <?php echo e($pesanan['nama_pelanggan']); ?> | <?php echo e($pesanan['nama_layanan'] ?: 'Cuci'); ?> | <?php echo (float) $pesanan['total_qty']; ?> kg | Rp <?php echo number_format((float) $pesanan['total_harga'], 0, ',', '.'); ?>
        </p>

        <!-- Ilustrasi Mesin Cuci & Keranjang Laundry (Sesuai Desain Figma) -->
        <div style="margin: 24px 0 28px; display: flex; align-items: center; justify-content: center;">
            <div style="position: relative; width: 176px; height: 144px; display: flex; align-items: center; justify-content: center;">
                <!-- Ilustrasi SVG Mesin Cuci, Keranjang & Tanaman -->
                <svg width="176" height="144" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block;">
                    <!-- Meja / Lantai -->
                    <ellipse cx="100" cy="148" rx="85" ry="8" fill="#EAF3FE"/>

                    <!-- Tanaman Kecil di Kiri -->
                    <rect x="25" y="125" width="16" height="18" rx="3" fill="#FBBF24"/>
                    <path d="M33 125 C30 115, 20 118, 22 108 C25 116, 32 120, 33 125 Z" fill="#34D399"/>
                    <path d="M33 125 C36 115, 46 118, 44 108 C41 116, 34 120, 33 125 Z" fill="#10B981"/>

                    <!-- Mesin Cuci Utama (Tengah) -->
                    <rect x="62" y="50" width="76" height="92" rx="14" fill="#FFFFFF" stroke="#93C5FD" stroke-width="4"/>
                    <!-- Panel Atas Mesin Cuci -->
                    <rect x="70" y="58" width="60" height="10" rx="3" fill="#F1F5F9"/>
                    <circle cx="118" cy="63" r="2.5" fill="#38BDF8"/>
                    <circle cx="124" cy="63" r="2.5" fill="#0085FF"/>
                    <!-- Pintu Kaca Mesin Cuci -->
                    <circle cx="100" cy="102" r="26" fill="#0085FF" stroke="#BAE6FD" stroke-width="5"/>
                    <circle cx="100" cy="102" r="16" fill="#2563EB"/>
                    <path d="M92 94 A14 14 0 0 1 112 104" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round"/>

                    <!-- Keranjang Laundry di Kanan -->
                    <path d="M148 100 L176 100 L171 140 L153 140 Z" fill="#BAE6FD" stroke="#38BDF8" stroke-width="2"/>
                    <line x1="152" y1="108" x2="155" y2="135" stroke="#0085FF" stroke-width="1.5"/>
                    <line x1="162" y1="108" x2="162" y2="135" stroke="#0085FF" stroke-width="1.5"/>
                    <line x1="172" y1="108" x2="169" y2="135" stroke="#0085FF" stroke-width="1.5"/>
                    <!-- Pakaian Menumpuk di Keranjang -->
                    <circle cx="156" cy="95" r="7" fill="#FB7185"/>
                    <circle cx="168" cy="94" r="8" fill="#FBBF24"/>
                    <circle cx="162" cy="88" r="7" fill="#818CF8"/>
                </svg>
            </div>
        </div>

        <!-- 2 Tombol Aksi Sesuai Figma -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <!-- Tombol 1: Lihat Pesanan (Solid Biru #0088FF) -->
            <a href="<?php echo $base; ?>transaksi/detail?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
               style="width: 100%; padding: 14px 20px; background-color: #0088FF; color: #FFFFFF; font-weight: 700; font-size: 14px; border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: background-color 0.2s; display: block; text-align: center; cursor: pointer;">
                Lihat Pesanan
            </a>

            <!-- Tombol 2: Buat Pesanan Baru (Biru Muda #E6F3FF / #DBEAFE) -->
            <a href="<?php echo $base; ?>transaksi/tambah"
               style="width: 100%; padding: 14px 20px; background-color: #E6F3FF; color: #0088FF; font-weight: 700; font-size: 14px; border-radius: 12px; border: 1px solid #93C5FD; transition: background-color 0.2s; display: block; text-align: center; cursor: pointer;">
                Buat Pesanan Baru
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
