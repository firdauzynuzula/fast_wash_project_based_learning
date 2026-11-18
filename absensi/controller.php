<?php
/**
 * Fast Wash - Modul Absensi: Lapisan Controller
 * Mengatur alur logika bisnis, otorisasi peran, validasi request, dan delegasi ke Model/View.
 */

require_once __DIR__ . '/model.php';

/**
 * Memastikan hanya pengguna dengan peran 'karyawan' yang dapat mengakses fitur absensi.
 */
function absensi_guard_karyawan(string $base): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $base);
        exit;
    }

    $role = $_SESSION['role'] ?? '';
    if ($role !== 'karyawan') {
        header('Location: ' . $base . 'dashboard');
        exit;
    }
}

/**
 * Controller untuk menampilkan halaman utama presensi dan riwayat absensi.
 */
function absensi_tampilkan_halaman(PDO $pdo, string $base): void
{
    absensi_guard_karyawan($base);

    $userId = (int) $_SESSION['user_id'];
    $idKaryawan = absensi_ambil_id_karyawan($pdo, $userId);

    if (!$idKaryawan) {
        http_response_code(403);
        exit('Akun karyawan tidak valid atau data profil karyawan belum terhubung.');
    }

    // Pengaman idempoten: jika karyawan sudah login sejak kemarin (pergantian hari),
    // otomatis lakukan check-in untuk tanggal hari ini.
    absensi_checkin_otomatis($pdo, $idKaryawan, $userId);

    // Ambil data absensi hari ini
    $hariIni     = date('Y-m-d');
    $today       = absensi_hari_ini($pdo, $idKaryawan, $hariIni);
    $sudahMasuk  = $today && !empty($today['jam_masuk']);
    $sudahKeluar = $today && !empty($today['jam_pulang']);

    // Filter dan paginasi riwayat
    $q       = trim($_GET['q'] ?? '');
    $status  = trim($_GET['status'] ?? '');
    $perPage = 7;
    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $totalData    = absensi_hitung_riwayat($pdo, $idKaryawan, $q, $status);
    $totalHalaman = max(1, (int) ceil($totalData / $perPage));
    $riwayat      = absensi_daftar_riwayat($pdo, $idKaryawan, $q, $status, $perPage, $offset);

    $pesan = trim($_GET['pesan'] ?? '');
    $title = 'Absensi';
    $route = 'absensi';

    // Panggil View
    require __DIR__ . '/view.php';
}

/**
 * Controller untuk memproses form aksi presensi (check-in / check-out).
 */
function absensi_proses_aksi(PDO $pdo, string $base): void
{
    absensi_guard_karyawan($base);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $base . 'absensi');
        exit;
    }

    csrf_check();

    $userId     = (int) $_SESSION['user_id'];
    $idKaryawan = absensi_ambil_id_karyawan($pdo, $userId);

    if (!$idKaryawan) {
        header('Location: ' . $base . 'absensi?pesan=' . urlencode('ID karyawan tidak ditemukan.'));
        exit;
    }

    $aksi  = trim($_POST['aksi'] ?? '');
    $pesan = '';

    if ($aksi === 'checkout') {
        $res   = absensi_checkout($pdo, $idKaryawan, $userId);
        $pesan = $res['message'];
    } elseif ($aksi === 'checkin') {
        $res   = absensi_checkin_otomatis($pdo, $idKaryawan, $userId);
        $pesan = $res['message'];
    } else {
        $pesan = 'Aksi presensi tidak dikenali.';
    }

    header('Location: ' . $base . 'absensi?pesan=' . urlencode($pesan));
    exit;
}
