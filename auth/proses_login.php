<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_default.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'auth/login.php');
    exit;
}

csrf_check();

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if (cek_login_default($username, $password)) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = 1;
    $_SESSION['nama']    = 'Admin';
    $_SESSION['role']    = 'admin';
    header('Location: ' . $base . 'dashboard');
    exit;
}

$_SESSION['flash_error'] = 'Username atau kata sandi salah.';
header('Location: ' . $base . 'auth/login');
exit;
