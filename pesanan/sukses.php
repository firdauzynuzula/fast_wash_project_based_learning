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
            pl.nama AS nama_pelanggan,
            (SELECT string_agg(l.nama_layanan, ', ')
               FROM detail_pesanan d JOIN layanan l ON l.id_layanan = d.id_layanan
              WHERE d.id_pesanan = p.id_pesanan) AS nama_layanan,
            (SELECT COALESCE(SUM(d.qty), 0) FROM detail_pesanan d WHERE d.id_pesanan = p.id_pesanan) AS total_qty
       FROM pesanan p
       JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
      WHERE p.id_pesanan IN ($placeholders)
      ORDER BY p.id_pesanan ASC"
);
$stmt->execute($ids);
$daftar = $stmt->fetchAll();

if (!$daftar) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

$linkLihat = count($daftar) === 1
    ? $base . 'pesanan/detail?id=' . (int) $daftar[0]['id_pesanan']
    : $base . 'pesanan';

$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Pesanan Baru', 'url' => 'pesanan/tambah'],
    ['label' => 'Pesanan Dibuat', 'url' => ''],
];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .sk-wrap { display: flex; justify-content: center; padding: 48px 16px 32px; }
    .sk-card { width: 100%; max-width: 560px; box-sizing: border-box; background: #fff; border-radius: 28px; padding: 44px 44px 44px; text-align: center; box-shadow: 0 8px 28px rgba(15,23,42,.06); }
    .sk-ico { width: 60px; height: 60px; margin: 0 auto; border-radius: 50%; background: #bfe9ff; display: flex; align-items: center; justify-content: center; }
    .sk-title { margin: 20px 0 0; font-size: 24px; font-weight: 700; color: #0a2a5e; line-height: 1.3; }
    .sk-info { margin-top: 10px; display: flex; flex-direction: column; gap: 4px; }
    .sk-info p { margin: 0; font-size: 12px; line-height: 1.5; color: #6b7280; }
    .sk-ill { margin-top: 28px; display: flex; justify-content: center; }
    .sk-btns { margin-top: 36px; display: flex; flex-direction: column; gap: 12px; }
    .sk-btn { display: block; width: 100%; box-sizing: border-box; padding: 15px 16px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; text-decoration: none; transition: background-color .15s; }
    .sk-btn-primary { background: #0085ff; color: #fff; }
    .sk-btn-primary:hover { background: #0070d8; }
    .sk-btn-soft { background: #d3ebff; color: #0085ff; }
    .sk-btn-soft:hover { background: #c2e4ff; }
    @media (max-width: 640px) {
        .sk-card { padding: 32px 22px; }
    }
</style>

<div class="sk-wrap">
    <div class="sk-card">

        <!-- Ikon centang -->
        <div class="sk-ico">
            <svg width="28" height="28" fill="none" stroke="#0085ff" stroke-width="3" viewBox="0 0 24 24" style="display:block;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
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

        <!-- Ilustrasi: mesin cuci, tanaman, keranjang -->
        <div class="sk-ill">
            <svg width="220" height="120" viewBox="0 0 200 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- bayangan lantai -->
                <ellipse cx="100" cy="102" rx="82" ry="4" fill="#e3eef9"/>

                <!-- sparkle -->
                <path d="M52 20v8M48 24h8" stroke="#7dd3fc" stroke-width="1.6" stroke-linecap="round"/>
                <circle cx="150" cy="14" r="2" stroke="#7dd3fc" stroke-width="1.4"/>

                <!-- mesin cuci -->
                <rect x="62" y="22" width="76" height="78" rx="8" fill="#f4f8fc" stroke="#b9c8da" stroke-width="2"/>
                <rect x="62" y="22" width="76" height="13" rx="6" fill="#e6eef8"/>
                <rect x="70" y="26" width="14" height="4" rx="2" fill="#cbd8e8"/>
                <circle cx="124" cy="28" r="2" fill="#38bdf8"/>
                <circle cx="131" cy="28" r="2" fill="#38bdf8"/>
                <circle cx="100" cy="68" r="25" fill="#fff" stroke="#a9ccf3" stroke-width="3"/>
                <circle cx="100" cy="68" r="19" fill="#0085ff"/>
                <path d="M83 70c6-5 11 5 17 0s11 5 17 0v2a17 17 0 01-34 0z" fill="#2b9bff"/>
                <path d="M90 58a14 14 0 0112-5" stroke="#8cc8ff" stroke-width="2.4" stroke-linecap="round"/>

                <!-- mainan susun di atas mesin -->
                <ellipse cx="124" cy="20" rx="9" ry="3.5" fill="#f9a8d4"/>
                <ellipse cx="124" cy="15.5" rx="6.5" ry="3" fill="#ec4899"/>
                <ellipse cx="124" cy="11.5" rx="4" ry="2.5" fill="#f472b6"/>
                <circle cx="124" cy="8" r="2" fill="#fbbf24"/>

                <!-- tanaman + tumpukan -->
                <rect x="22" y="86" width="26" height="6" rx="2" fill="#f59e0b"/>
                <rect x="18" y="92" width="34" height="5" rx="2" fill="#34d399"/>
                <rect x="14" y="97" width="42" height="5" rx="2" fill="#38bdf8"/>
                <path d="M29 86h12l-1.5-8h-9z" fill="#b45309"/>
                <path d="M35 78C27 72 27 62 33 55c6 6 8 15 2 23z" fill="#16a34a"/>
                <path d="M35 78c8-4 12-12 10-21-6 4-10 12-10 21z" fill="#4ade80"/>
                <path d="M35 78c-4-6-12-8-14-14 8 0 13 6 14 14z" fill="#22c55e"/>

                <!-- keranjang + busa -->
                <path d="M144 72h38l-4 26a4 4 0 01-4 3h-22a4 4 0 01-4-3z" fill="#7dd3fc" stroke="#38bdf8" stroke-width="2"/>
                <path d="M147 82h32M148 90h30" stroke="#e0f2fe" stroke-width="2" stroke-linecap="round"/>
                <circle cx="155" cy="68" r="8" fill="#e0f2fe" stroke="#bae6fd" stroke-width="1.5"/>
                <circle cx="167" cy="65" r="9" fill="#f0f9ff" stroke="#bae6fd" stroke-width="1.5"/>
                <circle cx="176" cy="70" r="6" fill="#e0f2fe" stroke="#bae6fd" stroke-width="1.5"/>
                <circle cx="150" cy="54" r="3" stroke="#7dd3fc" stroke-width="1.4"/>
                <circle cx="180" cy="52" r="2.2" stroke="#7dd3fc" stroke-width="1.4"/>
            </svg>
        </div>

        <div class="sk-btns">
            <a href="<?php echo $linkLihat; ?>" class="sk-btn sk-btn-primary">Lihat Pesanan</a>
            <a href="<?php echo $base; ?>pesanan/tambah" class="sk-btn sk-btn-soft">Buat Pesanan Baru</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
