<?php
$title = 'Pesanan Dibuat';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Mendukung ?ids=1,2,3 (banyak pesanan) maupun ?id=1
$raw = $_GET['ids'] ?? ($_GET['id'] ?? '');
$ids = array_values(array_filter(array_map('intval', explode(',', (string) $raw)), fn($v) => $v > 0));

if (!$ids) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare(
    "SELECT p.id_pesanan, p.kode_pesanan, p.total_harga,
            p.status_bayar, p.catatan, p.tanggal_pesan, p.token_nota,
            pl.nama AS nama_pelanggan, pl.no_telepon,
            k.nama AS nama_karyawan,
            (SELECT string_agg(l.nama_layanan, ', ')
               FROM detail_pesanan d JOIN layanan l ON l.id_layanan = d.id_layanan
              WHERE d.id_pesanan = p.id_pesanan) AS nama_layanan,
            (SELECT COALESCE(SUM(d.qty), 0) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS total_qty
       FROM pesanan p
       JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
       LEFT JOIN karyawan k ON k.id_karyawan = p.id_karyawan
      WHERE p.id_pesanan IN ($placeholders)
      ORDER BY p.id_pesanan ASC"
);
$stmt->execute($ids);
$daftar = $stmt->fetchAll();

if (!$daftar) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Halaman rincian menampilkan semua pesanan dalam transaksi yang sama
$linkLihat = $base . 'pesanan/detail?id=' . (int) $daftar[0]['id_pesanan'];

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Pesanan Baru', 'url' => 'pesanan/tambah'],
    ['label' => 'Pesanan Dibuat', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .sk-wrap { display: flex; justify-content: center; padding: 40px 0 48px; }
    .sk-card { background: #fff; border-radius: 28px; width: 100%; max-width: 480px; box-sizing: border-box; padding: 40px 40px 36px; text-align: center; box-shadow: 0 1px 3px rgba(15,23,42,.05); }
    .sk-ico { width: 52px; height: 52px; border-radius: 50%; background: #BFE6FF; margin: 0 auto; display: flex; align-items: center; justify-content: center; }
    .sk-title { font-size: 26px; font-weight: 700; color: #0A2A5E; margin: 20px 0 0; }
    .sk-info { margin-top: 10px; }
    .sk-info p { font-size: 12px; color: #6B7280; margin: 0 0 4px; line-height: 1.5; }
    .sk-ilu { display: flex; justify-content: center; margin: 26px 0 28px; }
    .sk-btn { display: block; width: 100%; box-sizing: border-box; padding: 14px 16px; border-radius: 12px; font-size: 14px; text-align: center; text-decoration: none; font-family: inherit; }
    .sk-btn-lihat { background: #0085FF; color: #fff; font-weight: 600; }
    .sk-btn-lihat:hover { background: #0070D8; }
    .sk-btn-baru { background: #CFE8FF; color: #0A3D8F; font-weight: 500; margin-top: 12px; }
    .sk-btn-baru:hover { background: #BFE0FF; }
    .sk-btn-cetak { background: #fff; color: #0085FF; border: 1px solid #93C5FD; font-weight: 600; margin-top: 12px; cursor: pointer; }
    .sk-btn-cetak:hover { background: #EFF6FF; }

    #print-area { display: none; }

    @media print {
        @page {
            margin: 0;
            size: auto;
        }
        html, body {
            width: 58mm;
            height: auto;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }
        body > .h-screen > aside,
        #sidebar-backdrop,
        header,
        .sk-card,
        .sk-wrap::before,
        .sk-wrap::after {
            display: none !important;
        }
        body > .h-screen,
        body > .h-screen > div.flex-1,
        main {
            display: block !important;
            width: 58mm !important; /* Ganti menjadi 80mm untuk printer thermal 80mm */
            max-width: none !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
        }
        .sk-wrap {
            display: block !important;
            padding: 0 !important;
        }
        #print-area {
            display: block;
            width: 58mm;
            margin: 0;
            padding: 3mm;
            box-sizing: border-box;
            font-family: "Courier New", Courier, monospace;
            color: #000;
            background: #fff;
        }
        .print-label {
            width: 52mm;
            margin: 0;
            padding: 2mm 0;
            box-sizing: border-box;
            page-break-after: always;
            break-after: page;
        }
        .print-label:last-child {
            page-break-after: auto;
            break-after: auto;
        }
        .print-label-header {
            text-align: center;
            font-weight: 700;
        }
        .print-label-header strong {
            display: block;
            font-size: 14pt;
        }
        .print-label-header span {
            display: block;
            font-size: 9pt;
            margin-top: 1mm;
        }
        .print-dashed {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
        .print-order-code {
            text-align: center;
            font-size: 15pt;
            font-weight: 800;
            margin: 2mm 0;
            word-break: break-word;
        }
        .print-row {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            font-size: 9pt;
            line-height: 1.35;
            margin: 1mm 0;
        }
        .print-row span:first-child {
            flex-shrink: 0;
            font-weight: 700;
        }
        .print-row span:last-child {
            text-align: right;
        }
        .print-payment {
            font-size: 11pt;
            font-weight: 900;
            text-align: center;
            margin: 2mm 0;
        }
        .print-center {
            text-align: center;
        }
    }
    @media (max-width: 520px) { .sk-card { padding: 32px 22px 28px; } }
</style>

<div class="sk-wrap">
    <div class="sk-card">

        <!-- Ikon centang -->
        <div class="sk-ico">
            <svg width="26" height="26" fill="none" stroke="#0085FF" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" style="display:block;">
                <path d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="sk-title">Pesanan dibuat</h1>

        <div class="sk-info">
            <?php foreach ($daftar as $d): ?>
                <p>
                    <?php echo e($d['kode_pesanan']); ?> for <?php echo e($d['nama_pelanggan']); ?>
                    | <?php echo e($d['nama_layanan'] ?: '-'); ?>
                    | <?php echo (float) $d['total_qty']; ?> kg
                    | Rp <?php echo number_format((float) $d['total_harga'], 0, '.', ','); ?>
                </p>
            <?php endforeach; ?>
        </div>

        <!-- Ilustrasi -->
        <div class="sk-ilu">
            <svg width="150" height="110" viewBox="0 0 160 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="30" y="14" width="84" height="88" rx="10" fill="#F0F9FF" stroke="#93C5FD" stroke-width="2"/>
                <rect x="30" y="14" width="84" height="12" rx="3" fill="#E0F2FE"/>
                <circle cx="72" cy="62" r="26" fill="#FFFFFF" stroke="#93C5FD" stroke-width="3"/>
                <circle cx="72" cy="62" r="19" fill="#0085FF"/>
                <circle cx="72" cy="62" r="9" fill="#2563EB"/>
                <circle cx="44" cy="21" r="2.5" fill="#38BDF8"/>
                <circle cx="52" cy="21" r="2.5" fill="#F472B6"/>
                <path d="M116 66h26l-3 34a4 4 0 01-4 3h-12a4 4 0 01-4-3z" fill="#38BDF8" stroke="#0EA5E9" stroke-width="2"/>
                <ellipse cx="129" cy="66" rx="15" ry="4" fill="#BAE6FD"/>
                <path d="M12 98h22" stroke="#34D399" stroke-width="3" stroke-linecap="round"/>
                <path d="M22 98c-6-8-2-16 2-22 4 6 8 14 2 22" fill="#4ADE80"/>
                <path d="M118 50l8-22 8 22z" fill="#F87171"/>
            </svg>
        </div>

        <a href="<?php echo $linkLihat; ?>" class="sk-btn sk-btn-lihat">Lihat Pesanan</a>
        <a href="<?php echo $base; ?>pesanan/tambah" class="sk-btn sk-btn-baru">Buat Pesanan Baru</a>
        <button type="button" id="btnCetakLabel" class="sk-btn sk-btn-cetak" onclick="window.print()">Cetak Ulang Label</button>
    </div>

    <section id="print-area" aria-label="Label keranjang cucian">
        <?php foreach ($daftar as $d): ?>
            <?php
            $statusBayarLabel = ($d['status_bayar'] === 'lunas') ? 'LUNAS' : 'BELUM BAYAR';
            $catatanLabel = trim((string) ($d['catatan'] ?? ''));
            $namaKasir = trim((string) ($d['nama_karyawan'] ?? '')) ?: '-';
            $tanggalCetak = date('d/m/Y H:i');
            ?>
            <article class="print-label">
                <div class="print-label-header">
                    <strong>FAST WASH</strong>
                    <span>LABEL KERANJANG</span>
                </div>

                <div class="print-dashed"></div>

                <div class="print-order-code"><?php echo e($d['kode_pesanan']); ?></div>

                <div class="print-row">
                    <span>Nama</span>
                    <span><?php echo e($d['nama_pelanggan']); ?></span>
                </div>
                <div class="print-row">
                    <span>Layanan</span>
                    <span><?php echo e($d['nama_layanan'] ?: '-'); ?></span>
                </div>
                <div class="print-row">
                    <span>Berat/Qty</span>
                    <span><?php echo rtrim(rtrim(number_format((float) $d['total_qty'], 2, '.', ''), '0'), '.'); ?> kg</span>
                </div>

                <div class="print-payment"><?php echo $statusBayarLabel; ?></div>

                <div class="print-row">
                    <span>Catatan</span>
                    <span><?php echo e($catatanLabel !== '' ? $catatanLabel : '-'); ?></span>
                </div>

                <div class="print-dashed"></div>

                <div class="print-row">
                    <span>Tanggal &amp; Jam</span>
                    <span><?php echo e($tanggalCetak); ?></span>
                </div>
                <div class="print-row">
                    <span>Kasir</span>
                    <span><?php echo e($namaKasir); ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.setTimeout(function () {
            window.print();
        }, 500);
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>