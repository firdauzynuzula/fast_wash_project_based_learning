<?php
$title = 'Rekap Absensi';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// Halaman khusus pemilik. Hapus blok ini kalau auth.php sudah punya pengecekan role sendiri.
if (!in_array($_SESSION['role'] ?? '', ['pemilik', 'admin'], true)) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

$listPath = 'absensi/rekap';

$breadcrumb = [
    ['label' => 'Rekap Absensi', 'url' => ''],
];

/*
 * DATA CONTOH (sesuai desain). Ganti dengan query ke tabel absensi kalau struktur tabelnya sudah ada.
 * Field: nama, peran, dot (warna titik peran), avatar (warna lingkaran inisial), shift (pagi|siang),
 *        tanggal, checkin, checkout (null = masih bertugas), telat (menit), durasi,
 *        status (hadir|terlambat|izin_sakit), ket_izin & verifikasi (khusus izin_sakit)
 */
$dataAbsensi = [
    ['nama' => 'Rani Paramitha',   'peran' => 'Kasir & Front Desk',      'dot' => '#16A34A', 'avatar' => '#DBE8FD', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => '07:58', 'checkout' => '17:02', 'telat' => 0,  'durasi' => '9j 4m',   'status' => 'hadir'],
    ['nama' => 'Dimas Aditya',     'peran' => 'Operasional Cuci',        'dot' => '#F59E0B', 'avatar' => '#A6D5FF', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => '08:14', 'checkout' => null,    'telat' => 14, 'durasi' => '5j 46m', 'status' => 'terlambat'],
    ['nama' => 'Firdauzy Nuzula',  'peran' => 'Petugas Setrika & Uap',   'dot' => '#16A34A', 'avatar' => '#DBE8FD', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => '07:50', 'checkout' => '17:00', 'telat' => 0,  'durasi' => '9j 10m',  'status' => 'hadir'],
    ['nama' => 'Areta Neila',      'peran' => 'Kasir & Admin Siang',     'dot' => '#16A34A', 'avatar' => '#DBE8FD', 'shift' => 'siang', 'tanggal' => '27 Sep 2026', 'checkin' => '11:55', 'checkout' => null,    'telat' => 0,  'durasi' => '2j 05m', 'status' => 'hadir'],
    ['nama' => 'Rizky Pratama',    'peran' => 'Staf Pengering Mesin',    'dot' => '#2563EB', 'avatar' => '#D0E0FE', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => null,    'checkout' => null,    'telat' => 0,  'durasi' => '',       'status' => 'izin_sakit', 'verifikasi' => 'Surat dokter terverifikasi', 'ket_izin' => 'Sakit Demam (1 Hari)'],
    ['nama' => 'Budi Santoso',     'peran' => 'Kurir Antar-Jemput',      'dot' => '#16A34A', 'avatar' => '#D0E0FE', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => '07:52', 'checkout' => '17:15', 'telat' => 0,  'durasi' => '9j 23m',  'status' => 'hadir'],
    ['nama' => 'Salsa Maharani',   'peran' => 'Petugas Setrika & Uap',   'dot' => '#16A34A', 'avatar' => '#DBE8FD', 'shift' => 'pagi',  'tanggal' => '27 Sep 2026', 'checkin' => '07:55', 'checkout' => '17:05', 'telat' => 0,  'durasi' => '9j 10m',  'status' => 'hadir'],
    ['nama' => 'Bagas Wicaksono',  'peran' => 'Kurir Antar-Jemput',      'dot' => '#16A34A', 'avatar' => '#D0E0FE', 'shift' => 'siang', 'tanggal' => '27 Sep 2026', 'checkin' => '12:01', 'checkout' => null,    'telat' => 0,  'durasi' => '1j 59m', 'status' => 'hadir'],
];

$labelShift  = ['pagi' => 'Shift Pagi', 'siang' => 'Shift Siang'];
$labelStatus = [
    'hadir'      => ['Hadir',      '#E8F7EF', '#0E7A47'],
    'terlambat'  => ['Terlambat',  '#FEF3DC', '#B45309'],
    'izin_sakit' => ['Izin Sakit', '#DCEBFF', '#0A5CC7'],
];

// Filter & pencarian
$cari    = trim($_GET['cari'] ?? '');
$fShift  = trim($_GET['shift'] ?? '');
$fStatus = trim($_GET['status'] ?? '');

$hasil = array_values(array_filter($dataAbsensi, function ($r) use ($cari, $fShift, $fStatus) {
    if ($cari !== '' && mb_stripos($r['nama'], $cari) === false) return false;
    if ($fShift !== '' && $r['shift'] !== $fShift) return false;
    if ($fStatus !== '' && $r['status'] !== $fStatus) return false;
    return true;
}));

// Ekspor CSV (data sesuai filter yang aktif)
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rekap-absensi-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tanggal', 'Nama', 'Peran', 'Shift', 'Check-in', 'Check-out', 'Durasi', 'Status']);
    foreach ($hasil as $r) {
        fputcsv($out, [
            $r['tanggal'], $r['nama'], $r['peran'], $labelShift[$r['shift']] ?? $r['shift'],
            $r['checkin'] ?? '-', $r['checkout'] ?? ($r['checkin'] ? 'Sedang Bertugas' : '-'),
            $r['durasi'] ?: '-', $labelStatus[$r['status']][0] ?? $r['status'],
        ]);
    }
    fclose($out);
    exit;
}

// Pagination (6 baris per halaman, sesuai desain)
$limit        = 6;
$total        = count($hasil);
$totalHalaman = max(1, (int) ceil($total / $limit));
$halamanAktif = min(max(1, (int) ($_GET['page'] ?? 1)), $totalHalaman);
$tampil       = array_slice($hasil, ($halamanAktif - 1) * $limit, $limit);

$qs = fn(int $page) => $base . $listPath . '?page=' . $page
    . '&cari=' . urlencode($cari) . '&shift=' . urlencode($fShift) . '&status=' . urlencode($fStatus);
$urlExport = $base . $listPath . '?export=csv'
    . '&cari=' . urlencode($cari) . '&shift=' . urlencode($fShift) . '&status=' . urlencode($fStatus);

$inisial = function (string $nama): string {
    $kata = preg_split('/\s+/', trim($nama));
    $hasil = mb_substr($kata[0] ?? '', 0, 1) . mb_substr($kata[1] ?? '', 0, 1);
    return mb_strtoupper($hasil);
};

$iconChevron = '<svg width="16" height="16" fill="none" stroke="#6B7280" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .ra-card { background:#FFFFFF; border-radius:24px; padding:32px; border:1px solid #EEF2F7; box-shadow:0 4px 20px rgba(15,23,42,.04); margin-bottom:32px; }
    .ra-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; background:#EAF3FE; color:#0A5CC7; font-size:12px; font-weight:600; }
    .ra-title { font-size:30px; line-height:1.2; font-weight:800; color:#0B2545; margin:10px 0 6px; letter-spacing:-.01em; }
    .ra-desc { font-size:14px; line-height:1.85; color:#475569; max-width:610px; margin:0 0 24px; }

    .ra-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-bottom:24px; }
    .ra-search { position:relative; flex:1 1 280px; }
    .ra-select-wrap { position:relative; flex:0 0 150px; }
    .ra-field { width:100%; height:44px; box-sizing:border-box; background:#FFFFFF; border:1px solid #E5EAF2; border-radius:12px; font-size:14px; color:#334155; outline:none; box-shadow:0 1px 2px rgba(15,23,42,.03); }
    .ra-field:focus { border-color:#0085FF; }
    .ra-input { padding:0 16px 0 42px; }
    .ra-select { appearance:none; -webkit-appearance:none; -moz-appearance:none; padding:0 36px 0 16px; cursor:pointer; }
    .ra-icon-left { position:absolute; top:0; bottom:0; left:14px; display:flex; align-items:center; pointer-events:none; }
    .ra-icon-right { position:absolute; top:0; bottom:0; right:14px; display:flex; align-items:center; pointer-events:none; }
    .ra-export { flex:0 0 auto; height:44px; display:inline-flex; align-items:center; gap:8px; padding:0 20px; background:#0085FF; color:#FFFFFF; font-size:14px; font-weight:600; border-radius:12px; box-shadow:0 2px 6px rgba(0,133,255,.25); white-space:nowrap; text-decoration:none; }
    .ra-export:hover { background:#0070D8; }

    .ra-table-wrap { border:1px solid #E5EAF2; border-radius:16px; overflow:hidden; overflow-x:auto; }
    .ra-table { width:100%; min-width:900px; border-collapse:collapse; table-layout:fixed; text-align:left; }
    .ra-table thead th { background:#0085FF; color:#FFFFFF; font-size:14px; font-weight:600; padding:16px 24px; }
    .ra-table tbody td { padding:16px 24px; vertical-align:middle; border-bottom:1px solid #EEF2F7; font-size:14px; color:#475569; }
    .ra-table tbody tr:last-child td { border-bottom:0; }
    .ra-table tbody tr:hover { background:#FAFCFF; }
    .ra-center { text-align:center !important; }

    .ra-tgl { font-weight:700; color:#0F172A; font-size:14px; }
    .ra-tgl-sub { color:#64748B; font-size:13px; margin-top:2px; }
    .ra-emp { display:flex; align-items:center; gap:12px; }
    .ra-avatar { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; color:#1D4ED8; flex:0 0 36px; }
    .ra-name { font-weight:700; color:#0F172A; font-size:15px; }
    .ra-role { display:flex; align-items:center; gap:6px; color:#475569; font-size:13px; margin-top:2px; }
    .ra-dot { width:7px; height:7px; border-radius:50%; flex:0 0 7px; }
    .ra-shift { display:inline-block; padding:6px 16px; border-radius:999px; background:#EAF3FE; color:#0A7DE0; font-size:13px; font-weight:500; }

    .ra-line { font-size:14px; color:#64748B; line-height:1.5; }
    .ra-line b { color:#0F172A; font-weight:700; }
    .ra-line b.ra-late { color:#D01C1C; }
    .ra-durasi-row { display:flex; align-items:center; gap:8px; margin-top:6px; }
    .ra-chip { display:inline-block; padding:3px 10px; border-radius:8px; background:#F1F5F9; color:#475569; font-size:12px; }
    .ra-status { display:inline-block; padding:5px 16px; border-radius:999px; font-size:12px; font-weight:600; }

    .ra-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:20px; font-size:13px; color:#475569; }
    .ra-foot strong { color:#0F172A; }
    .ra-pager { display:flex; align-items:center; gap:6px; }
    .ra-page { min-width:34px; height:34px; box-sizing:border-box; display:inline-flex; align-items:center; justify-content:center; padding:0 14px; border:1px solid #E5EAF2; border-radius:10px; background:#FFFFFF; color:#334155; font-size:13px; font-weight:500; text-decoration:none; }
    a.ra-page:hover { background:#F8FAFC; }
    .ra-page.aktif { background:#0085FF; border-color:#0085FF; color:#FFFFFF; font-weight:700; }
    .ra-page.mati { color:#94A3B8; }
</style>

<div class="ra-card">

    <!-- Badge -->
    <span class="ra-badge">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
        Presensi &amp; Rekap Kehadiran Operasional
    </span>

    <h1 class="ra-title">Rekap Absensi Karyawan</h1>
    <p class="ra-desc">Pantau rekam jejak kehadiran staf kasir, operator cuci, kurir, dan penyetrika Fast Wash secara transparan, akuntabel, dan real-time tanpa celah manipulasi.</p>

    <!-- Toolbar -->
    <form method="get" action="<?php echo $base . $listPath; ?>" class="ra-toolbar">
        <div class="ra-search">
            <div class="ra-icon-left">
                <svg width="16" height="16" fill="none" stroke="#6B7280" stroke-width="2" viewBox="0 0 24 24" style="display:block;"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
            </div>
            <input type="text" name="cari" value="<?php echo e($cari); ?>" placeholder="Cari nama karyawan" class="ra-field ra-input">
        </div>

        <div class="ra-select-wrap">
            <select name="shift" onchange="this.form.submit()" class="ra-field ra-select">
                <option value="">Semua Shift</option>
                <?php foreach ($labelShift as $v => $l): ?>
                    <option value="<?php echo $v; ?>" <?php echo $fShift === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="ra-icon-right"><?php echo $iconChevron; ?></div>
        </div>

        <div class="ra-select-wrap">
            <select name="status" onchange="this.form.submit()" class="ra-field ra-select">
                <option value="">Semua Status</option>
                <?php foreach ($labelStatus as $v => $l): ?>
                    <option value="<?php echo $v; ?>" <?php echo $fStatus === $v ? 'selected' : ''; ?>><?php echo $l[0]; ?></option>
                <?php endforeach; ?>
            </select>
            <div class="ra-icon-right"><?php echo $iconChevron; ?></div>
        </div>

        <a href="<?php echo e($urlExport); ?>" class="ra-export">
            <svg width="16" height="16" fill="none" stroke="#FFFFFF" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Ekspor Rekap
        </a>
    </form>

    <!-- Tabel -->
    <div class="ra-table-wrap">
        <table class="ra-table">
            <thead>
                <tr>
                    <th style="width:15%;">Tanggal</th>
                    <th style="width:26%;">Karyawan &amp; Peran</th>
                    <th style="width:16%;" class="ra-center">Shift Presensi</th>
                    <th style="width:28%;">Rincian Kehadiran</th>
                    <th style="width:15%;" class="ra-center">Status &amp; Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tampil)): ?>
                    <tr>
                        <td colspan="5" class="ra-center" style="padding:40px 24px;">
                            <p style="font-size:14px; font-weight:600; color:#334155; margin:0;">Data absensi tidak ditemukan</p>
                            <p style="font-size:13px; color:#94A3B8; margin:6px 0 0;">Coba ubah kata kunci atau filter yang dipilih.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tampil as $r): ?>
                        <?php
                        $izin    = $r['status'] === 'izin_sakit';
                        $selesai = !$izin && $r['checkout'] !== null;
                        $stl     = $labelStatus[$r['status']] ?? [ucfirst($r['status']), '#F1F5F9', '#475569'];
                        $jamKecil = $r['checkin'] ? str_replace(':', '.', $r['checkin']) . ' WIB' : '-';
                        ?>
                        <tr>
                            <!-- Tanggal -->
                            <td>
                                <div class="ra-tgl"><?php echo e($r['tanggal']); ?></div>
                                <div class="ra-tgl-sub"><?php echo e($jamKecil); ?></div>
                            </td>

                            <!-- Karyawan & Peran -->
                            <td>
                                <div class="ra-emp">
                                    <span class="ra-avatar" style="background:<?php echo e($r['avatar']); ?>;"><?php echo e($inisial($r['nama'])); ?></span>
                                    <div>
                                        <div class="ra-name"><?php echo e($r['nama']); ?></div>
                                        <div class="ra-role"><span class="ra-dot" style="background:<?php echo e($r['dot']); ?>;"></span><?php echo e($r['peran']); ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Shift -->
                            <td class="ra-center">
                                <span class="ra-shift"><?php echo e($labelShift[$r['shift']] ?? ucfirst($r['shift'])); ?></span>
                            </td>

                            <!-- Rincian Kehadiran -->
                            <td>
                                <?php if ($izin): ?>
                                    <div class="ra-line">Izin Tidak Masuk:</div>
                                    <div class="ra-line"><b><?php echo e($r['verifikasi'] ?? '-'); ?></b></div>
                                    <div class="ra-durasi-row"><span class="ra-chip">Ket: <?php echo e($r['ket_izin'] ?? '-'); ?></span></div>
                                <?php else: ?>
                                    <div class="ra-line">Check-in: <b class="<?php echo $r['telat'] > 0 ? 'ra-late' : ''; ?>"><?php echo e($r['checkin']); ?> WIB</b></div>
                                    <?php if ($r['telat'] > 0): ?>
                                        <div class="ra-line">(Terlambat +<?php echo (int) $r['telat']; ?>m)</div>
                                    <?php endif; ?>
                                    <?php if ($r['checkout'] !== null): ?>
                                        <div class="ra-line">Check-Out: <b><?php echo e($r['checkout']); ?> WIB</b></div>
                                    <?php elseif ($r['telat'] === 0): ?>
                                        <div class="ra-line">Check-Out: <b>Sedang Bertugas</b></div>
                                    <?php endif; ?>
                                    <div class="ra-durasi-row">
                                        <span class="ra-chip">Durasi: <?php echo e($r['durasi']); ?><?php echo $selesai ? '' : ' (Aktif)'; ?></span>
                                        <?php if ($selesai): ?>
                                            <svg width="18" height="18" fill="none" stroke="#16A34A" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td class="ra-center">
                                <span class="ra-status" style="background:<?php echo $stl[1]; ?>; color:<?php echo $stl[2]; ?>;"><?php echo e($stl[0]); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer tabel -->
    <div class="ra-foot">
        <span>Menampilkan <strong><?php echo count($tampil); ?></strong> dari <strong><?php echo $total; ?></strong> karyawan aktif hari ini</span>

        <div class="ra-pager">
            <?php if ($halamanAktif > 1): ?>
                <a href="<?php echo e($qs($halamanAktif - 1)); ?>" class="ra-page">Sebelumnya</a>
            <?php else: ?>
                <span class="ra-page mati">Sebelumnya</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                <?php if ($i === $halamanAktif): ?>
                    <span class="ra-page aktif"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo e($qs($i)); ?>" class="ra-page"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($halamanAktif < $totalHalaman): ?>
                <a href="<?php echo e($qs($halamanAktif + 1)); ?>" class="ra-page">Berikutnya</a>
            <?php else: ?>
                <span class="ra-page mati">Berikutnya</span>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>