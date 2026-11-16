<?php
$title = 'Nota Pesanan';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Ambil data header nota dari view v_nota_header
$stmtHeader = $pdo->prepare("SELECT * FROM v_nota_header WHERE id_pesanan = :id");
$stmtHeader->execute(['id' => $id]);
$nota = $stmtHeader->fetch();

if (!$nota) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Ambil item rincian nota
$stmtItems = $pdo->prepare("
    SELECT d.*, l.nama_layanan, l.satuan
      FROM detail_pesanan d
      JOIN layanan l ON l.id_layanan = d.id_layanan
     WHERE d.id_pesanan = :id
");
$stmtItems->execute(['id' => $id]);
$items = $stmtItems->fetchAll();

$isLunas = ($nota['status_bayar'] === 'lunas');
$metodeBayar = strtoupper($nota['metode_pembayaran'] ?: 'Tunai');

// Teks template pesan WhatsApp
$waMsg = rawurlencode(
    "🧾 *NOTA TRANSAKSI FAST WASH LAUNDRY*\n\n" .
    "No. Nota: *{$nota['kode_pesanan']}*\n" .
    "Pelanggan: {$nota['nama_pelanggan']}\n" .
    "Tanggal Masuk: " . date('d M Y, H:i', strtotime($nota['tanggal_masuk'])) . "\n" .
    "Estimasi Selesai: " . ($nota['estimasi_selesai'] ? date('d M Y', strtotime($nota['estimasi_selesai'])) : '-') . "\n\n" .
    "Total Pembayaran: *Rp " . number_format((float) $nota['total_harga'], 0, ',', '.') . "*\n" .
    "Status: *" . ($isLunas ? 'LUNAS / PAID' : 'BELUM LUNAS') . "* (" . $metodeBayar . ")\n\n" .
    "Pengambilan barang wajib menunjukkan pesan/nota ini. Terima kasih telah mempercayakan pakaian Anda di Fast Wash Laundry!"
);
$waCleanPhone = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', (string)$nota['no_whatsapp_pelanggan']));
$waUrl = "https://wa.me/{$waCleanPhone}?text={$waMsg}";

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Rincian Pesanan', 'url' => 'pesanan/detail?id=' . $nota['id_pesanan']],
    ['label' => 'Nota Pesanan', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
    header, aside, #btn-open-sidebar, .no-print, nav {
        display: none !important;
    }
    body, .h-screen, div {
        overflow: visible !important;
        height: auto !important;
        background: white !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
    #notaCardPrint {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 auto !important;
        max-width: 700px !important;
    }
}
</style>

<!-- Action Bar Atas Sesuai Desain Figma (Kirim WhatsApp & Cetak Nota) -->
<div class="no-print flex items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-2">
        <a href="<?php echo $base; ?>pesanan/detail?id=<?php echo (int) $nota['id_pesanan']; ?>"
           class="text-xs text-slate-500 hover:text-[#0085ff] flex items-center gap-1 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Rincian Pesanan</span>
        </a>
    </div>

    <div class="flex items-center gap-3">
        <!-- Tombol Kirim WhatsApp (Border Hijau, Latar Putih) -->
        <a href="<?php echo $waUrl; ?>"
           target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-emerald-400 text-emerald-500 hover:bg-emerald-50 font-medium text-xs rounded-2xl transition-colors shadow-xs">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2.05 21.95l4.908-1.288A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/>
            </svg>
            <span>Kirim WhatsApp</span>
        </a>

        <!-- Tombol Cetak Nota Fisik / Thermal (Biru Solid #0085ff) -->
        <button type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0085ff] hover:bg-[#0070d8] text-white font-semibold text-xs rounded-2xl shadow-sm shadow-[#0085ff]/20 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Cetak Nota Fisik / Thermal</span>
        </button>
    </div>
</div>

<!-- Kartu Nota Sesuai Rancangan UI/UX Designer Figma (100% Identik) -->
<div class="max-w-4xl mx-auto mb-12">
    <div id="notaCardPrint" class="bg-white rounded-3xl p-8 sm:p-10 shadow-xs border border-slate-100">

        <!-- Baris Header Nota: Logo & Kontak di Kiri, Status & Kode di Kanan -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-slate-100">
            <!-- Kiri: Brand & Alamat -->
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#0085ff] text-white flex items-center justify-center font-bold text-lg shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="4" y="2" width="16" height="20" rx="2"/>
                            <circle cx="12" cy="13" r="4"/>
                            <line x1="8" y1="6" x2="8.01" y2="6"/>
                            <line x1="12" y1="6" x2="12.01" y2="6"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-slate-800">Fast Wash</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">Jl. Melati No. 12, Kota Malang</p>
                <p class="text-xs text-slate-400">WhatsApp Customer Care: 0812-3456-7890</p>
            </div>

            <!-- Kanan: Status Bayar, No Nota & Waktu -->
            <div class="text-left sm:text-right space-y-1.5">
                <div>
                    <?php if ($isLunas): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>LUNAS / PAID</span>
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>BELUM LUNAS</span>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-2xl font-extrabold text-slate-800 tracking-tight">
                    <?php echo e($nota['kode_pesanan']); ?>

                </div>
                <p class="text-xs text-slate-400">
                    <?php echo date('d F Y, H:i', strtotime($nota['tanggal_masuk'])); ?> WIB
                </p>
            </div>
        </div>

        <!-- Baris 4 Metadata Utama: Nama, Tgl Masuk, Estimasi, Kasir -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-6 border-b border-slate-100 text-xs">
            <div>
                <span class="text-slate-400">Nama Pelanggan</span>
                <div class="font-bold text-slate-800 text-sm mt-0.5"><?php echo e($nota['nama_pelanggan']); ?></div>
            </div>
            <div>
                <span class="text-slate-400">Tanggal Masuk</span>
                <div class="font-bold text-slate-800 text-sm mt-0.5"><?php echo date('d M Y', strtotime($nota['tanggal_masuk'])); ?></div>
            </div>
            <div>
                <span class="text-slate-400">Estimasi Selesai</span>
                <div class="font-bold text-[#0085ff] text-sm mt-0.5">
                    <?php echo $nota['estimasi_selesai'] ? date('d M Y', strtotime($nota['estimasi_selesai'])) : '-'; ?>

                </div>
            </div>
            <div>
                <span class="text-slate-400">Kasir / Petugas</span>
                <div class="font-bold text-slate-800 text-sm mt-0.5"><?php echo e($nota['kasir'] ?: 'Petugas'); ?></div>
            </div>
        </div>

        <!-- Tabel Item Layanan -->
        <div class="py-6 border-b border-slate-100">
            <table class="w-full text-left text-xs text-slate-600">
                <thead>
                    <tr class="text-slate-400 font-semibold border-b border-slate-100 pb-2">
                        <th class="pb-3 font-semibold">Layanan & Deksripsi</th>
                        <th class="pb-3 text-center font-semibold">Jumlah / Berat</th>
                        <th class="pb-3 text-right font-semibold">Tarif Satuan</th>
                        <th class="pb-3 text-right font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="py-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-[#0085ff] flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                                            <circle cx="12" cy="12" r="2"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm"><?php echo e($it['nama_layanan']); ?></div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Pewangi: Ocean Fresh • Kemasan Plastik Rapi</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 text-center font-semibold text-slate-700 text-sm">
                                <?php echo (float) $it['qty']; ?> <?php echo e($it['satuan']); ?>

                            </td>
                            <td class="py-4 text-right text-slate-600 text-sm">
                                Rp <?php echo number_format((float) $it['harga_satuan'], 0, ',', '.'); ?> / <?php echo e($it['satuan']); ?>

                            </td>
                            <td class="py-4 text-right font-bold text-slate-800 text-sm">
                                Rp <?php echo number_format((float) $it['subtotal'], 0, ',', '.'); ?>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Baris Bawah: Box Metode Pembayaran di Kiri & Rincian Total di Kanan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-100">
            <!-- Box Metode Pembayaran Kiri -->
            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-5 text-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px]">METODE PEMBAYARAN</span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-100 text-[#0085ff]">Otomatis Terverifikasi</span>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-white border border-slate-200 text-slate-800">
                        <?php echo e($metodeBayar); ?>

                    </span>
                    <span class="font-bold text-slate-800">
                        <?php echo $isLunas ? 'Lunas - No. Ref: QR-' . substr(md5((string)$nota['id_pesanan']), 0, 6) . '-FW' : 'Menunggu Pelunasan'; ?>

                    </span>
                </div>
                <p class="text-slate-400 text-[11px]">
                    Divalidasi pada <?php echo date('d F Y, H:i', strtotime($nota['tanggal_masuk'])); ?> WIB
                </p>
                <div class="flex items-center gap-1.5 text-[11px] text-[#0085ff] pt-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Bukti pembayaran sah secara elektronik oleh Fast Wash System</span>
                </div>
            </div>

            <!-- Rincian Biaya Kanan -->
            <div class="space-y-2.5 text-xs text-slate-600 sm:pl-8">
                <div class="flex justify-between">
                    <span>Subtotal Layanan</span>
                    <span class="font-semibold text-slate-800">Rp <?php echo number_format((float) $nota['subtotal_layanan'], 0, ',', '.'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>Diskon Promosi</span>
                    <span class="font-semibold text-slate-800">Rp 0</span>
                </div>
                <div class="flex justify-between">
                    <span>Biaya Tambahan / Antar</span>
                    <span class="font-semibold text-slate-800">Rp <?php echo number_format((float) $nota['biaya_tambahan'], 0, ',', '.'); ?></span>
                </div>
                <!-- Garis Putus-putus -->
                <div class="border-b border-dashed border-slate-300 my-2"></div>
                <div class="flex items-center justify-between pt-1">
                    <span class="font-bold text-slate-800 text-sm">Total Pembayaran</span>
                    <span class="text-2xl font-extrabold text-[#0085ff]">
                        Rp <?php echo number_format((float) $nota['total_harga'], 0, ',', '.'); ?>

                    </span>
                </div>
            </div>
        </div>

        <!-- Footer Nota: Catatan, Syarat Ketentuan & Ucapan Terima Kasih -->
        <div class="pt-6 text-xs text-slate-500 space-y-2">
            <p>
                <strong class="text-slate-700">Catatan Pesanan:</strong>
                <?php echo e($nota['catatan'] ?: 'Pakaian katun & kemeja kerja, mohon pisahkan kerah khusus.'); ?>

            </p>
            <div>
                <strong class="text-slate-700">Syarat & Ketentuan:</strong>
                <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">
                    Pengambilan barang wajib menunjukkan nota digital ini atau struk fisik. Klaim kehilangan atau kerusakan maksimal 1×24 jam sejak pesanan diterima. Barang yang tidak diambil dalam tempo 30 hari di luar tanggung jawab kami.
                </p>
            </div>
            <p class="text-xs font-semibold text-[#0085ff] pt-2">
                Terima kasih telah mempercayakan laundry Anda kepada Fast Wash!
            </p>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
