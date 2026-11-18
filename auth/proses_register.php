<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'auth/register');
    exit;
}

csrf_check();

$nama            = trim($_POST['nama_lengkap'] ?? '');
$email           = trim($_POST['email'] ?? '');
$noWhatsapp      = trim($_POST['no_whatsapp'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
$setuju          = isset($_POST['setuju']);

if ($nama === '' || $email === '' || $noWhatsapp === '' || $password === '' || $passwordConfirm === '') {
    $_SESSION['flash_error'] = 'Semua kolom wajib diisi.';
    header('Location: ' . $base . 'auth/register');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = 'Format email tidak valid.';
    header('Location: ' . $base . 'auth/register');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['flash_error'] = 'Kata sandi minimal 8 karakter.';
    header('Location: ' . $base . 'auth/register');
    exit;
}

if ($password !== $passwordConfirm) {
    $_SESSION['flash_error'] = 'Konfirmasi kata sandi tidak cocok.';
    header('Location: ' . $base . 'auth/register');
    exit;
}

if (!$setuju) {
    $_SESSION['flash_error'] = 'Anda harus menyetujui Ketentuan Layanan & Privasi Fast Wash.';
    header('Location: ' . $base . 'auth/register');
    exit;
}

$hasil = daftarkan_karyawan($pdo, $nama, $email, $noWhatsapp, $password);
if ($hasil['success']) {
    $_SESSION['flash_success'] = $hasil['message'];
    header('Location: ' . $base . 'auth/login');
    exit;
}

$_SESSION['flash_error'] = $hasil['message'];
header('Location: ' . $base . 'auth/register');
exit;
