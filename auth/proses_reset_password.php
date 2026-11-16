<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'auth/login');
    exit;
}

csrf_check();

$token           = trim($_POST['token'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

// 1. Validasi Token via Database
$tokenData = $token !== '' ? validasi_token_reset($pdo, $token) : null;
if ($tokenData === null) {
    $_SESSION['flash_error'] = 'Tautan reset kata sandi tidak valid atau telah kedaluwarsa.';
    header('Location: ' . $base . 'auth/forgot_password');
    exit;
}

// 2. Validasi Kata Sandi Baru
if (strlen($password) < 8) {
    $_SESSION['flash_error'] = 'Kata sandi baru minimal harus 8 karakter.';
    header('Location: ' . $base . 'auth/reset_password?token=' . urlencode($token));
    exit;
}

if ($password !== $passwordConfirm) {
    $_SESSION['flash_error'] = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
    header('Location: ' . $base . 'auth/reset_password?token=' . urlencode($token));
    exit;
}

// 3. Perbarui kata sandi secara atomik di database dan batalkan token serta sesi lama
$berhasil = perbarui_kata_sandi_reset($pdo, $token, $password);

if (!$berhasil) {
    $_SESSION['flash_error'] = 'Terjadi kesalahan saat memperbarui kata sandi. Silakan coba lagi.';
    header('Location: ' . $base . 'auth/reset_password?token=' . urlencode($token));
    exit;
}

// 4. Set notifikasi sukses dan arahkan pengguna ke form login
$_SESSION['flash_success'] = 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.';
header('Location: ' . $base . 'auth/login');
exit;
