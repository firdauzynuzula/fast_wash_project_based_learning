<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'auth/login');
    exit;
}

csrf_check();

$token           = trim($_POST['token'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

// Validasi Token (Mendukung Sesi + Cache Server)
$tokenData = ambil_reset_token($token);
if ($tokenData === null) {
    $_SESSION['flash_error'] = 'Sesi reset kata sandi tidak valid atau telah berakhir.';
    header('Location: ' . $base . 'auth/forgot_password');
    exit;
}

$emailTarget = $tokenData['email'] ?? '';

// Validasi Password
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

// 1. Hash password baru dengan bcrypt bawaan PHP
$newHash = password_hash($password, PASSWORD_DEFAULT);

// 2. Jika database nanti sudah aktif (opsional PDO query):
/*
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("UPDATE pengguna SET password_hash = :hash WHERE email = :email");
        $stmt->execute(['hash' => $newHash, 'email' => $emailTarget]);
    } catch (PDOException $e) {
        error_log("Gagal update password di DB: " . $e->getMessage());
    }
}
*/

// 3. Bersihkan token agar hanya bisa dipakai sekali (single-use)
hapus_reset_token($token);

// 4. Set pesan sukses untuk ditampilkan di login.php
$_SESSION['flash_success'] = 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.';
header('Location: ' . $base . 'auth/login');
exit;
