<?php
$title = 'Absensi';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Pastikan fungsi CSRF tersedia (dimuat hanya kalau belum ada)
if (!function_exists('csrf_token')) {
    require_once __DIR__ . '/../includes/csrf.php';
}

date_default_timezone_set('Asia/Jakarta');

// ---------- Helper lokal ----------
function tglIndo($tgl, $panjang = false) {
    $hari  = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $hariP = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan  = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $bulanP = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($tgl);
    $w = (int)date('w', $t);
    $m = (int)date('n', $t);
    if ($panjang) {
        return $hariP[$w] . ', ' . date('j', $t) . ' ' . $bulanP[$m] . ' ' . date('Y', $t);
    }
    return $hari[$w] . ', ' . date('j', $t) . ' ' . $bulan[$m];
}

function durasi($masuk, $keluar) {
    if (!$masuk || !$keluar) return '-';
    $selisih = strtotime($keluar) - strtotime($masuk);
    if ($selisih < 0) return '-';
    return floor($selisih / 3600) . ' jam ' . floor(($selisih % 3600) / 60) . ' menit';
}

function jam($j) { return $j ? date('H:i', strtotime($j)) : '-'; }

// ---------- User yang login ----------
$userId = (int)($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));

// ---------- Absensi hari ini ----------
$hariIni = date('Y-m-d');
$st = $pdo->prepare('SELECT * FROM absensi WHERE id_karyawan = :u AND tanggal = :t');
$st->execute([':u' => $userId, ':t' => $hariIni]);
$today = $st->fetch();

$sudahMasuk  = $today && $today['jam_masuk'];
$sudahKeluar = $today && $today['jam_pulang'];

// ---------- Filter + pagination riwayat ----------
$q       = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? '';
$perPage = 7;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = 'WHERE id_karyawan = :u';
$params = [':u' => $userId];
if ($q !== '') {
    $where .= ' AND LOWER(status) LIKE :q';
    $params[':q'] = '%' . strtolower($q) . '%';
}
if (in_array($status, ['Hadir', 'Terlambat', 'Izin'], true)) {
    $where .= ' AND LOWER(status) = :s';
    $params[':s'] = strtolower($status);
}

$st = $pdo->prepare("SELECT COUNT(*) FROM absensi $where");
$st->execute($params);
$totalData    = (int)$st->fetchColumn();
$totalHalaman = max(1, (int)ceil($totalData / $perPage));

$st = $pdo->prepare("SELECT * FROM absensi $where ORDER BY tanggal DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $st->bindValue($k, $v);
$st->bindValue(':limit', $perPage, PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$riwayat = $st->fetchAll();

// Kelas badge per status (kunci: huruf pertama kapital)
$badge = [
    'Hadir'     => 'abs-hadir',
    'Terlambat' => 'abs-terlambat',
    'Izin'      => 'abs-izin',
];

function urlHalaman($p) {
    $qs = $_GET; $qs['page'] = $p;
    return '?' . http_build_query($qs);
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
    .abs-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 1px 3px rgba(15,23,42,.05); margin-bottom:20px; }
    .abs-clock-card { padding:28px 24px; text-align:center; }
    .abs-date { font-size:14px; color:#6b7280; }
    .abs-clock { font-size:56px; line-height:1.1; font-weight:700; color:#2563eb; margin:12px 0; letter-spacing:.02em; }
    .abs-boxes { display:grid; grid-template-columns:1fr 1fr; gap:16px; max-width:448px; margin:20px auto; }
    .abs-box { border:1px solid #e5e7eb; border-radius:12px; padding:12px 16px; text-align:left; background:#f8fafc; }
    .abs-box .lbl { font-size:12px; font-weight:500; color:#6b7280; }
    .abs-box .val { font-size:24px; font-weight:700; color:#9ca3af; }
    .abs-box .val.has { color:#1f2937; }
    .abs-box.in { background:#ecfdf5; border-color:#a7f3d0; }
    .abs-box.in .lbl { color:#15803d; }
    .abs-box.in .val { color:#15803d; }
    .abs-pesan { font-size:14px; color:#0369a1; margin-bottom:12px; }
    .abs-btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; max-width:448px; margin:0 auto;
               border:0; border-radius:12px; padding:14px; font-size:16px; font-weight:600; color:#fff; cursor:pointer; }
    .abs-btn svg { width:20px; height:20px; }
    .abs-btn-in  { background:#16a34a; }
    .abs-btn-in:hover  { background:#15803d; }
    .abs-btn-out { background:#e11d48; }
    .abs-btn-out:hover { background:#be123c; }
    .abs-btn-off { background:#e5e7eb; color:#6b7280; cursor:not-allowed; }

    .abs-filter { display:flex; flex-wrap:wrap; gap:12px; padding:16px; }
    .abs-search { position:relative; flex:1; min-width:200px; }
    .abs-search svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; }
    .abs-search input { width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:9px 12px 9px 36px; font-size:14px; }
    .abs-select { border:1px solid #e5e7eb; border-radius:10px; padding:9px 12px; font-size:14px; background:#fff; }

    .abs-table-wrap { padding:0 16px; overflow-x:auto; }
    .abs-table { width:100%; border-collapse:separate; border-spacing:0; font-size:14px; }
    .abs-table thead th { background:#0a84ff; color:#fff; font-weight:500; padding:12px 16px; text-align:center; }
    .abs-table thead th:first-child { border-top-left-radius:10px; border-bottom-left-radius:10px; }
    .abs-table thead th:last-child  { border-top-right-radius:10px; border-bottom-right-radius:10px; }
    .abs-table tbody td { padding:14px 16px; text-align:center; color:#374151; border-bottom:1px solid #f1f5f9; }
    .abs-table tbody tr:last-child td { border-bottom:0; }
    .abs-table tbody tr:hover td { background:#f8fafc; }
    .abs-empty { padding:32px 16px !important; color:#9ca3af !important; }

    .abs-badge { display:inline-block; font-size:12px; font-weight:500; padding:2px 12px; border-radius:999px; border:1px solid #e5e7eb; background:#f9fafb; color:#4b5563; }
    .abs-hadir     { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .abs-terlambat { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
    .abs-izin      { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

    .abs-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding:16px; margin-top:8px;
                background:#f8fafc; border-top:1px solid #f1f5f9; border-radius:0 0 16px 16px; font-size:12px; color:#6b7280; }
    .abs-pager { display:flex; align-items:center; gap:6px; }
    .abs-pager a, .abs-pager span { border:1px solid #e5e7eb; background:#fff; border-radius:6px; padding:6px 12px; color:#374151; text-decoration:none; }
    .abs-pager a:hover { background:#f3f4f6; }
    .abs-pager .on  { background:#0a84ff; border-color:#0a84ff; color:#fff; }
    .abs-pager .off { color:#9ca3af; background:#f9fafb; }
</style>

        <!-- Kartu jam + check-in/out -->
        <div class="abs-card abs-clock-card">
            <p class="abs-date"><?php echo e(tglIndo($hariIni, true)); ?></p>

            <!-- JAM BERJALAN: diisi oleh JavaScript di bawah -->
            <p id="jam-digital" class="abs-clock"><?php echo date('H.i.s'); ?></p>

            <div class="abs-boxes">
                <div class="abs-box <?php echo $sudahMasuk ? 'in' : ''; ?>">
                    <p class="lbl">Check-in</p>
                    <p class="val <?php echo $sudahMasuk ? 'has' : ''; ?>">
                        <?php echo $sudahMasuk ? e(jam($today['jam_masuk'])) : '--:--'; ?>
                    </p>
                </div>
                <div class="abs-box">
                    <p class="lbl">Check-out</p>
                    <p class="val <?php echo $sudahKeluar ? 'has' : ''; ?>">
                        <?php echo $sudahKeluar ? e(jam($today['jam_pulang'])) : '--:--'; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($_GET['pesan'])): ?>
                <p class="abs-pesan"><?php echo e($_GET['pesan']); ?></p>
            <?php endif; ?>

            <form action="proses" method="post">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <?php if (!$sudahMasuk): ?>
                    <input type="hidden" name="aksi" value="checkin">
                    <button type="submit" class="abs-btn abs-btn-in">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Check-in
                    </button>
                <?php elseif (!$sudahKeluar): ?>
                    <input type="hidden" name="aksi" value="checkout">
                    <button type="submit" class="abs-btn abs-btn-out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Check-out
                    </button>
                <?php else: ?>
                    <button type="button" disabled class="abs-btn abs-btn-off">Absensi hari ini selesai</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Riwayat -->
        <div class="abs-card">
            <form method="get" class="abs-filter">
                <div class="abs-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Cari status kehadiran">
                </div>
                <select name="status" onchange="this.form.submit()" class="abs-select">
                    <option value="">Semua Status</option>
                    <?php foreach (['Hadir', 'Terlambat', 'Izin'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div class="abs-table-wrap">
                <table class="abs-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Durasi Kehadiran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$riwayat): ?>
                        <tr><td colspan="5" class="abs-empty">Belum ada riwayat absensi.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r):
                        $label = ucfirst(strtolower((string)$r['status']));
                    ?>
                        <tr>
                            <td><?php echo e(tglIndo($r['tanggal'])); ?></td>
                            <td><?php echo e(jam($r['jam_masuk'])); ?></td>
                            <td><?php echo e(jam($r['jam_pulang'])); ?></td>
                            <td><?php echo e(durasi($r['jam_masuk'], $r['jam_pulang'])); ?></td>
                            <td><span class="abs-badge <?php echo $badge[$label] ?? ''; ?>"><?php echo e($label); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="abs-foot">
                <span>Menampilkan <b><?php echo count($riwayat); ?></b> dari <b><?php echo $totalData; ?></b> Absensi</span>
                <div class="abs-pager">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo urlHalaman($page - 1); ?>">Sebelumnya</a>
                    <?php else: ?>
                        <span class="off">Sebelumnya</span>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $totalHalaman; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="on"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="<?php echo urlHalaman($p); ?>"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalHalaman): ?>
                        <a href="<?php echo urlHalaman($page + 1); ?>">Berikutnya</a>
                    <?php else: ?>
                        <span class="off">Berikutnya</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<!-- ====== JAM BERJALAN (JavaScript) ====== -->
<script>
(function () {
    // Waktu server (ms) dari PHP, lalu hitung selisihnya dengan jam perangkat.
    var serverMs = <?php echo (int)(microtime(true) * 1000); ?>;
    var offset   = serverMs - Date.now();
    var el       = document.getElementById('jam-digital');

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var now = new Date(Date.now() + offset);
        var wib = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
        el.textContent = pad(wib.getHours()) + '.' + pad(wib.getMinutes()) + '.' + pad(wib.getSeconds());
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>