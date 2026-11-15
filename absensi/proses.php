<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index');
    exit;
}

// TODO: panggil verifikasi CSRF sesuai fungsi di includes/csrf.php
// contoh: csrf_verify();

// SESUAIKAN dengan session di includes/auth.php milikmu
$userId = (int)($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
$aksi   = $_POST['aksi'] ?? '';
$hari   = date('Y-m-d');
$jamNow = date('H:i:s');
$pesan  = '';

$st = $pdo->prepare('SELECT * FROM absensi WHERE user_id = :u AND tanggal = :t');
$st->execute([':u' => $userId, ':t' => $hari]);
$today = $st->fetch();

if ($aksi === 'checkin') {
    if ($today && $today['jam_masuk']) {
        $pesan = 'Kamu sudah check-in hari ini.';
    } else {
        // Batas masuk 08:00, lewat dari itu dianggap Terlambat
        $status = ($jamNow <= '08:00:59') ? 'Hadir' : 'Terlambat';
        $ins = $pdo->prepare('INSERT INTO absensi (user_id, tanggal, jam_masuk, status)
                              VALUES (:u, :t, :j, :s)');
        $ins->execute([':u' => $userId, ':t' => $hari, ':j' => $jamNow, ':s' => $status]);
        $pesan = 'Check-in berhasil pukul ' . substr($jamNow, 0, 5) . '.';
    }
} elseif ($aksi === 'checkout') {
    if (!$today || !$today['jam_masuk']) {
        $pesan = 'Kamu belum check-in hari ini.';
    } elseif ($today['jam_keluar']) {
        $pesan = 'Kamu sudah check-out hari ini.';
    } else {
        $up = $pdo->prepare('UPDATE absensi SET jam_keluar = :j WHERE id = :id');
        $up->execute([':j' => $jamNow, ':id' => $today['id']]);
        $pesan = 'Check-out berhasil pukul ' . substr($jamNow, 0, 5) . '.';
    }
}

header('Location: index?pesan=' . urlencode($pesan));
exit;