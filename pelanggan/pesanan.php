<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$token = trim((string) ($_GET['t'] ?? ''));
$isValidToken = (bool) preg_match('/^[a-f0-9]{32}$/i', $token);

$pesananList    = [];
$itemsByPesanan = [];

if ($isValidToken) {
    $stmtHeader = $pdo->prepare("
        SELECT id_pesanan, kode_pesanan, tanggal_masuk, estimasi_selesai, waktu_selesai,
               nama_pelanggan, no_whatsapp_pelanggan, kasir, subtotal_layanan,
               biaya_tambahan, total_harga, status_cucian, status_bayar, catatan,
               metode_pembayaran, tanggal_bayar, token_nota
          FROM v_nota_header
         WHERE token_nota = :token
         ORDER BY id_pesanan ASC
    ");
    $stmtHeader->execute(['token' => $token]);
    $pesananList = $stmtHeader->fetchAll(PDO::FETCH_ASSOC);

    if ($pesananList) {
        $ids = array_column($pesananList, 'id_pesanan');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtItems = $pdo->prepare("
            SELECT id_detail_pesanan, id_pesanan, nama_layanan, qty, satuan, harga_satuan, subtotal, catatan
              FROM v_nota_item
             WHERE id_pesanan IN ($placeholders)
             ORDER BY id_detail_pesanan ASC
        ");
        $stmtItems->execute($ids);
        $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allItems as $it) {
            $itemsByPesanan[$it['id_pesanan']][] = $it;
        }
    }
}

if (!$pesananList) {
    http_response_code(404);
}

function formatTanggalIndo(?string $datetime): string
{
    if (!$datetime) return '-';
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $time = strtotime($datetime);
    if (!$time) return '-';
    $d = date('j', $time);
    $m = (int) date('n', $time);
    $y = date('Y', $time);
    return $d . ' ' . ($bulanIndo[$m] ?? '') . ' ' . $y;
}

// Normalisasi status pengerjaan
function normalisasiStatus(?string $st): string
{
    $st = strtolower(trim((string) $st));
    if ($st === 'pending') return 'diterima';
    if (in_array($st, ['dicuci', 'dikeringkan', 'disetrika'], true)) return 'diproses';
    if (in_array($st, ['diterima', 'diproses', 'selesai', 'diambil'], true)) return $st;
    return 'diterima';
}

$kodeUtama = $pesananList ? $pesananList[0]['kode_pesanan'] : 'Tidak Ditemukan';
$pageTitle = 'Fast Wash - ' . ($pesananList ? 'Pesanan ' . $kodeUtama : 'Nota Tidak Ditemukan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    <title><?php echo e($pageTitle); ?></title>
    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Stylesheet Tailwind CSS v4 Terkompilasi -->
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/style.css'); ?>">
</head>

<body class="bg-[#eef4fb] font-jakarta text-slate-800 antialiased min-h-screen flex justify-center selection:bg-brand-100">
    <!-- Mobile Screen Container -->
    <main class="w-full max-w-[420px] min-h-screen bg-[#f1f6fc] pb-12 flex flex-col relative shadow-2xl">
        
        <?php if (!$pesananList): ?>
            <!-- TAMPILAN NOTA TIDAK DITEMUKAN (404) -->
            <div class="px-5 pt-16 pb-12 flex-1 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mb-5 shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-slate-900 mb-2">Nota Tidak Ditemukan</h1>
                <p class="text-xs text-slate-500 max-w-[280px] leading-relaxed mb-6">
                    Tautan nota digital yang Anda akses tidak valid atau pesanan belum terdaftar dalam sistem Fast Wash.
                </p>
                <div class="bg-white rounded-2xl p-4 border border-slate-200 text-xs text-slate-600 w-full mb-6">
                    Pastikan tautan yang Anda buka sesuai dengan pesan WhatsApp resmi yang dikirimkan oleh kasir Fast Wash.
                </div>
            </div>
        <?php else: ?>
            <!-- TAMPILAN NOTA VALID -->
            <div class="px-4 space-y-4 pt-1 flex-1">
                <!-- Header Breadcrumb -->
                <div class="flex items-center justify-between pt-5 pb-2 px-1">
                    <nav aria-label="Breadcrumb" class="flex items-center space-x-1.5 text-[15px] font-semibold">
                        <span class="text-slate-700 font-bold">Pesanan <?php echo e($kodeUtama); ?></span>
                    </nav>
                </div>

                <!-- DAFTAR KARTU PESANAN (TIMELINE & STATUS) -->
                <?php foreach ($pesananList as $idx => $p): ?>
                    <?php
                    $idPesanan = (int) $p['id_pesanan'];
                    $items     = $itemsByPesanan[$idPesanan] ?? [];
                    $namaLayananArr = array_unique(array_filter(array_column($items, 'nama_layanan')));
                    $namaLayanan = !empty($namaLayananArr) ? implode(' + ', $namaLayananArr) : 'Laundry Reguler';
                    
                    $totalQty = 0;
                    $satuan   = 'kg';
                    foreach ($items as $it) {
                        $totalQty += (float) $it['qty'];
                        if (!empty($it['satuan'])) $satuan = $it['satuan'];
                    }

                    $statusRaw   = normalisasiStatus($p['status_cucian']);
                    $statusBayar = strtolower((string) $p['status_bayar']);
                    ?>
                    <section class="bg-white rounded-3xl p-5 shadow-card border border-white relative overflow-hidden" data-order-card="<?php echo e($p['kode_pesanan']); ?>">
                        <!-- Header Kartu -->
                        <div class="flex items-start justify-between">
                            <div>
                                <!-- Badge Status Bayar -->
                                <span class="inline-block px-3 py-1 rounded-full text-[11px] font-semibold mb-2 border <?php
                                    echo ($statusBayar === 'lunas')
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-100/80'
                                        : (($statusBayar === 'sebagian')
                                            ? 'bg-sky-50 text-sky-700 border-sky-100/80'
                                            : 'bg-[#fff4e5] text-[#d97706] border-amber-100/80');
                                ?>" data-badge-bayar="<?php echo e($p['kode_pesanan']); ?>">
                                    <?php
                                    echo ($statusBayar === 'lunas') ? 'Lunas' : (($statusBayar === 'sebagian') ? 'Sebagian' : 'Belum Lunas');
                                    ?>
                                </span>
                                <h2 class="text-lg font-bold text-slate-900 tracking-tight"><?php echo e($namaLayanan); ?></h2>
                                <p class="text-[11.5px] text-slate-400 mt-0.5 leading-snug">
                                    <?php echo e($p['kode_pesanan']); ?> · <?php echo e($p['nama_pelanggan']); ?> · <?php echo (float) $totalQty; ?> <?php echo e($satuan); ?> · pesanan dibuat <?php echo formatTanggalIndo($p['tanggal_masuk']); ?>
                                </p>
                            </div>

                            <!-- Laundry Illustration Icon -->
                            <div class="relative w-16 h-14 shrink-0 flex items-center justify-end select-none">
                                <svg class="absolute left-0 bottom-1 w-5 h-7" fill="none" viewBox="0 0 24 32">
                                    <path d="M12 2C10 7 6 10 6 14C6 17 9 19 12 19C15 19 18 17 18 14C18 10 14 7 12 2Z" fill="#34D399"></path>
                                    <path d="M6 14C2 12 0 16 0 18C0 20 4 21 7 19" fill="#10B981"></path>
                                    <path d="M5 20L7 30H17L19 20H5Z" fill="#F59E0B"></path>
                                    <line stroke="#059669" stroke-width="2" x1="12" x2="12" y1="8" y2="20"></line>
                                </svg>
                                <div class="w-10 h-12 bg-white rounded-lg border-2 border-brand-500 relative flex flex-col items-center justify-center p-1 shadow-sm">
                                    <div class="w-7 h-7 rounded-full border-2 border-brand-500 bg-sky-50 flex items-center justify-center">
                                        <div class="w-3.5 h-3.5 rounded-full border border-brand-500 border-dashed"></div>
                                    </div>
                                    <div class="w-7 h-1 bg-brand-100 rounded mt-1"></div>
                                </div>
                                <div class="w-6 h-8 bg-sky-100 rounded-sm border-2 border-sky-400 -ml-1 mt-3 flex items-center justify-center shadow-xs">
                                    <div class="w-full h-full flex flex-col justify-around py-0.5 px-0.5">
                                        <span class="w-full h-[1.5px] bg-sky-400"></span>
                                        <span class="w-full h-[1.5px] bg-sky-400"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-header Status -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-slate-800">Status Pengerjaan</span>
                                </div>
                                <span class="px-3 py-0.5 text-xs font-semibold rounded-full border <?php
                                    echo ($statusRaw === 'selesai')
                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                        : (($statusRaw === 'diambil')
                                            ? 'bg-slate-100 text-slate-600 border-slate-200'
                                            : 'bg-[#e3f2fd] text-brand-600 border border-brand-100');
                                ?>" data-badge-status="<?php echo e($p['kode_pesanan']); ?>">
                                    <?php
                                    $labelStatus = [
                                        'diterima' => 'Diterima',
                                        'diproses' => 'Diproses',
                                        'selesai'  => 'Selesai',
                                        'diambil'  => 'Diambil'
                                    ];
                                    echo $labelStatus[$statusRaw] ?? 'Diterima';
                                    ?>
                                </span>
                            </div>

                            <!-- Vertical Timeline -->
                            <div class="space-y-4 pl-1" data-timeline="<?php echo e($p['kode_pesanan']); ?>" data-status="<?php echo e($statusRaw); ?>">
                                <?php
                                $steps = [
                                    'diterima' => ['label' => 'Diterima', 'desc' => 'Pakaian diterima & ditimbang'],
                                    'diproses' => ['label' => 'Diproses', 'desc' => 'Proses pencucian & setrika rapi'],
                                    'selesai'  => ['label' => 'Selesai', 'desc' => 'Pakaian bersih siap diambil'],
                                    'diambil'  => ['label' => 'Diambil', 'desc' => 'Pesanan sudah diserahkan']
                                ];
                                $orderKeys = ['diterima', 'diproses', 'selesai', 'diambil'];
                                $curIdx = array_search($statusRaw, $orderKeys);
                                if ($curIdx === false) $curIdx = 0;

                                foreach ($orderKeys as $i => $stKey):
                                    $isLast = ($i === count($orderKeys) - 1);
                                    $isPassed = ($i < $curIdx);
                                    $isActive = ($i === $curIdx);
                                ?>
                                    <div class="flex items-start space-x-3.5 relative" data-step-key="<?php echo $stKey; ?>">
                                        <div class="flex flex-col items-center">
                                            <?php if ($isPassed): ?>
                                                <!-- Step Checked -->
                                                <div class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center z-10 shadow-sm step-ico">
                                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path>
                                                    </svg>
                                                </div>
                                                <?php if (!$isLast): ?>
                                                    <div class="w-[2px] h-7 bg-brand-500 mt-1 step-line"></div>
                                                <?php endif; ?>
                                            <?php elseif ($isActive): ?>
                                                <!-- Step Active -->
                                                <div class="w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center z-10 ring-4 ring-brand-100 shadow-sm step-ico">
                                                    <?php if ($stKey === 'diterima'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                    <?php elseif ($stKey === 'diproses'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                            <rect height="18" rx="2.5" stroke-width="2" width="14" x="5" y="3"></rect>
                                                            <circle cx="12" cy="13" r="3.8" stroke-width="1.8"></circle>
                                                            <circle cx="8.5" cy="6.5" fill="currentColor" r="0.8"></circle>
                                                            <circle cx="12" cy="6.5" fill="currentColor" r="0.8"></circle>
                                                        </svg>
                                                    <?php elseif ($stKey === 'selesai'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!$isLast): ?>
                                                    <div class="w-[2px] h-7 bg-slate-200 mt-1 step-line"></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <!-- Step Inactive / Pending -->
                                                <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center z-10 step-ico">
                                                    <?php if ($stKey === 'selesai'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 6h16M5 10h14M7 14h10M9 18h6" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <?php elseif ($stKey === 'diambil'): ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                                    <?php else: ?>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle></svg>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!$isLast): ?>
                                                    <div class="w-[2px] h-7 bg-slate-200 mt-1 step-line"></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pt-0.5">
                                            <p class="text-sm font-bold <?php echo ($isActive || $isPassed) ? 'text-slate-900' : 'text-slate-400'; ?> step-title">
                                                <?php echo e($steps[$stKey]['label']); ?>
                                            </p>
                                            <p class="text-[11px] text-slate-400 step-desc">
                                                <?php echo e($steps[$stKey]['desc']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>

                <!-- RINGKASAN PESANAN -->
                <?php
                $namaPelanggan   = $pesananList[0]['nama_pelanggan'];
                $listKode        = implode(', ', array_column($pesananList, 'kode_pesanan'));
                $totalGrandHarga = array_sum(array_column($pesananList, 'total_harga'));
                
                $semuaLayanan = [];
                $semuaQty     = 0;
                $satuanQty    = 'kg';
                foreach ($itemsByPesanan as $itGroup) {
                    foreach ($itGroup as $it) {
                        $semuaLayanan[] = $it['nama_layanan'];
                        $semuaQty += (float) $it['qty'];
                        if (!empty($it['satuan'])) $satuanQty = $it['satuan'];
                    }
                }
                $strLayanan = !empty($semuaLayanan) ? implode(', ', array_unique($semuaLayanan)) : 'Laundry';

                // Catatan gabungan
                $catatanList = array_filter(array_map('trim', array_column($pesananList, 'catatan')));
                $strCatatan = !empty($catatanList) ? implode(' | ', array_unique($catatanList)) : '-';

                // Metode pembayaran
                $metodeArr = array_filter(array_column($pesananList, 'metode_pembayaran'));
                $metodeStr = !empty($metodeArr) ? strtoupper((string) reset($metodeArr)) : '-';

                // Status bayar keseluruhan
                $semuaLunas = true;
                foreach ($pesananList as $p) {
                    if (strtolower((string) $p['status_bayar']) !== 'lunas') {
                        $semuaLunas = false;
                        break;
                    }
                }
                $statusBayarGabungan = $semuaLunas ? 'Lunas' : 'Belum Lunas';

                // Estimasi selesai
                $estimasiArr = array_filter(array_column($pesananList, 'estimasi_selesai'));
                $estimasiMax = !empty($estimasiArr) ? max($estimasiArr) : null;
                ?>
                <section class="bg-white rounded-3xl p-6 shadow-card border border-white space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Ringkasan Pesanan</h2>
                        <span class="text-xs font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-md border border-brand-100">
                            <?php echo e($kodeUtama); ?>

                        </span>
                    </div>

                    <dl class="space-y-3 text-[13.5px]">
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Nomor Nota</dt>
                            <dd class="text-slate-900 font-semibold text-right"><?php echo e($listKode); ?></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Pelanggan</dt>
                            <dd class="text-slate-900 font-semibold"><?php echo e($namaPelanggan); ?></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Layanan</dt>
                            <dd class="text-slate-900 font-medium text-right"><?php echo e($strLayanan); ?></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Total Berat/Qty</dt>
                            <dd class="text-slate-900 font-medium"><?php echo (float) $semuaQty; ?> <?php echo e($satuanQty); ?></dd>
                        </div>
                        <div class="flex justify-between items-start">
                            <dt class="text-slate-400 font-normal shrink-0 mr-2">Catatan</dt>
                            <dd class="text-slate-900 font-medium text-right"><?php echo e($strCatatan); ?></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Pembayaran</dt>
                            <dd class="text-slate-900 font-medium"><?php echo e($metodeStr); ?></dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-slate-400 font-normal">Status Pembayaran</dt>
                            <dd class="text-slate-900 font-semibold" id="summaryStatusBayar"><?php echo e($statusBayarGabungan); ?></dd>
                        </div>
                        <?php if ($estimasiMax): ?>
                            <div class="flex justify-between items-center">
                                <dt class="text-slate-400 font-normal">Estimasi Siap</dt>
                                <dd class="text-slate-900 font-medium"><?php echo formatTanggalIndo($estimasiMax); ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>

                    <div class="pt-1">
                        <hr class="border-t border-slate-100">
                    </div>

                    <!-- Total Price -->
                    <div class="flex justify-between items-center pt-0.5">
                        <span class="text-base font-bold text-slate-900">Total</span>
                        <span class="text-xl font-extrabold text-brand-600">Rp <?php echo number_format((float) $totalGrandHarga, 0, ',', '.'); ?></span>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($pesananList): ?>
    <script>
    (function () {
        const TOKEN = <?php echo json_encode($token); ?>;
        const BASE_URL = <?php echo json_encode($base); ?>;
        let timer = null;
        let isPolling = false;

        const orderSteps = ['diterima', 'diproses', 'selesai', 'diambil'];
        const stepIcons = {
            checked: `<svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"></path></svg>`,
            diterima: `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>`,
            diproses: `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect height="18" rx="2.5" stroke-width="2" width="14" x="5" y="3"></rect><circle cx="12" cy="13" r="3.8" stroke-width="1.8"></circle><circle cx="8.5" cy="6.5" fill="currentColor" r="0.8"></circle><circle cx="12" cy="6.5" fill="currentColor" r="0.8"></circle></svg>`,
            selesai: `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
            diambil: `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-linecap="round" stroke-linejoin="round"></path></svg>`,
            inactive: `<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 6h16M5 10h14M7 14h10M9 18h6" stroke-linecap="round" stroke-linejoin="round"></path></svg>`
        };

        function normalisasi(st) {
            st = String(st || '').toLowerCase().trim();
            if (st === 'pending') return 'diterima';
            if (['dicuci', 'dikeringkan', 'disetrika'].includes(st)) return 'diproses';
            if (orderSteps.includes(st)) return st;
            return 'diterima';
        }

        function updateCardStatus(kode, statusRaw, statusBayar) {
            const st = normalisasi(statusRaw);

            // Update Badge Status Pengerjaan
            const badgeStatus = document.querySelector(`[data-badge-status="${kode}"]`);
            if (badgeStatus) {
                const labels = { diterima: 'Diterima', diproses: 'Diproses', selesai: 'Selesai', diambil: 'Diambil' };
                badgeStatus.textContent = labels[st] || 'Diterima';
                badgeStatus.className = 'px-3 py-0.5 text-xs font-semibold rounded-full border ' +
                    (st === 'selesai' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                     (st === 'diambil' ? 'bg-slate-100 text-slate-600 border-slate-200' :
                                         'bg-[#e3f2fd] text-brand-600 border border-brand-100'));
            }

            // Update Badge Status Bayar
            const badgeBayar = document.querySelector(`[data-badge-bayar="${kode}"]`);
            if (badgeBayar && statusBayar) {
                const sb = String(statusBayar).toLowerCase().trim();
                badgeBayar.textContent = (sb === 'lunas') ? 'Lunas' : ((sb === 'sebagian') ? 'Sebagian' : 'Belum Lunas');
                badgeBayar.className = 'inline-block px-3 py-1 rounded-full text-[11px] font-semibold mb-2 border ' +
                    (sb === 'lunas' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/80' :
                     (sb === 'sebagian' ? 'bg-sky-50 text-sky-700 border-sky-100/80' :
                                          'bg-[#fff4e5] text-[#d97706] border-amber-100/80'));
            }

            // Update Timeline
            const timelineEl = document.querySelector(`[data-timeline="${kode}"]`);
            if (!timelineEl) return;

            const curIdx = orderSteps.indexOf(st);
            const stepRows = timelineEl.querySelectorAll('[data-step-key]');

            stepRows.forEach((row, idx) => {
                const stKey = row.getAttribute('data-step-key');
                const icoEl = row.querySelector('.step-ico');
                const lineEl = row.querySelector('.step-line');
                const titleEl = row.querySelector('.step-title');

                if (idx < curIdx) {
                    // Passed / Checked
                    if (icoEl) {
                        icoEl.className = 'w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center z-10 shadow-sm step-ico';
                        icoEl.innerHTML = stepIcons.checked;
                    }
                    if (lineEl) lineEl.className = 'w-[2px] h-7 bg-brand-500 mt-1 step-line';
                    if (titleEl) titleEl.className = 'text-sm font-bold text-slate-900 step-title';
                } else if (idx === curIdx) {
                    // Active
                    if (icoEl) {
                        icoEl.className = 'w-7 h-7 rounded-full bg-brand-600 text-white flex items-center justify-center z-10 ring-4 ring-brand-100 shadow-sm step-ico';
                        icoEl.innerHTML = stepIcons[stKey] || stepIcons.checked;
                    }
                    if (lineEl) lineEl.className = 'w-[2px] h-7 bg-slate-200 mt-1 step-line';
                    if (titleEl) titleEl.className = 'text-sm font-bold text-slate-900 step-title';
                } else {
                    // Inactive
                    if (icoEl) {
                        icoEl.className = 'w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center z-10 step-ico';
                        icoEl.innerHTML = stepIcons[stKey] || stepIcons.inactive;
                    }
                    if (lineEl) lineEl.className = 'w-[2px] h-7 bg-slate-200 mt-1 step-line';
                    if (titleEl) titleEl.className = 'text-sm font-bold text-slate-400 step-title';
                }
            });
        }

        async function fetchStatus() {
            if (isPolling) return;
            isPolling = true;

            try {
                const res = await fetch(BASE_URL + 'nota/status?t=' + encodeURIComponent(TOKEN), {
                    cache: 'no-store'
                });
                if (!res.ok) return;

                const data = await res.json();
                if (!data || !data.ok || !Array.isArray(data.pesanan)) return;

                let allDiambil = true;
                let allLunas = true;

                data.pesanan.forEach(item => {
                    const st = normalisasi(item.status);
                    if (st !== 'diambil') allDiambil = false;
                    if (String(item.status_bayar).toLowerCase() !== 'lunas') allLunas = false;

                    updateCardStatus(item.kode_pesanan, item.status, item.status_bayar);
                });

                const summaryBayar = document.getElementById('summaryStatusBayar');
                if (summaryBayar) {
                    summaryBayar.textContent = allLunas ? 'Lunas' : 'Belum Lunas';
                }

                // Jika semua pesanan sudah diambil, hentikan polling
                if (allDiambil) {
                    stopPolling();
                    return;
                }
            } catch (err) {
                // Abaikan kesalahan koneksi berkala
            } finally {
                isPolling = false;
            }
        }

        function startPolling() {
            if (timer) return;
            timer = setInterval(fetchStatus, 8000);
        }

        function stopPolling() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                fetchStatus();
                startPolling();
            } else {
                stopPolling();
            }
        });

        startPolling();
    })();
    </script>
    <?php endif; ?>
</body>
</html>
