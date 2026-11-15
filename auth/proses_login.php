<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . '');
    exit;
}

csrf_check();

$username     = trim($_POST['username'] ?? '');
$password     = (string) ($_POST['password'] ?? '');
$selectedRole = in_array($_POST['role'] ?? '', ['pemilik', 'karyawan'], true) ? $_POST['role'] : 'karyawan';
$ingat        = isset($_POST['ingat']);

$user = verifikasi_login($pdo, $username, $password, $selectedRole);

if ($user) {
    mulai_sesi($pdo, $user, $ingat);
    header('Location: ' . $base . 'dashboard');
    exit;
}

$_SESSION['flash_error'] = 'Email/nomor HP, kata sandi, atau peran salah.';
header('Location: ' . $base . '');
exit;
