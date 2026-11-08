<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_default.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $base . 'auth/login');
    exit;
}

csrf_check();

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$ingat    = isset($_POST['ingat']);

if ($username === '' || $password === '') {
    $_SESSION['flash_error'] = 'Email/Nomor HP dan kata sandi wajib diisi.';
    header('Location: ' . $base . 'auth/login');
    exit;
}

// 1. Cek kredensial ke database
$user = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare('
            SELECT id_pengguna, email, no_telepon, password_hash, role, is_active, nama 
            FROM v_login_identitas 
            WHERE email = ? OR no_telepon = ? 
            LIMIT 1
        ');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $user = null;
    }
}

// 2. Verifikasi kata sandi
if ($user && password_verify($password, $user['password_hash'])) {
    if (!$user['is_active']) {
        $_SESSION['flash_error'] = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin.';
        header('Location: ' . $base . 'auth/login');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id_pengguna'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['email']   = $user['email'];

    // Fitur Ingat Saya
    if ($ingat && isset($pdo)) {
        try {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $stmtSesi = $pdo->prepare('
                INSERT INTO sesi_login (id_pengguna, token_hash, waktu_kadaluarsa, ingat_saya) 
                VALUES (?, ?, NOW() + INTERVAL \'30 days\', true)
            ');
            $stmtSesi->execute([$user['id_pengguna'], $tokenHash]);
            setcookie('remember_token', $user['id_pengguna'] . ':' . $token, time() + (86400 * 30), '/', '', false, true);
        } catch (Exception $e) {
            // Abaikan kegagalan token sesi
        }
    }

    header('Location: ' . $base . 'dashboard');
    exit;
}

// Fallback admin default
if (cek_login_default($username, $password)) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = 1;
    $_SESSION['nama']    = 'Admin';
    $_SESSION['role']    = 'admin';
    header('Location: ' . $base . 'dashboard');
    exit;
}

$_SESSION['flash_error'] = 'Email/Nomor HP atau kata sandi salah.';
header('Location: ' . $base . 'auth/login');
exit;
