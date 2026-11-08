<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Hapus status sesi_login di database jika token ingat saya digunakan
if (!empty($_COOKIE['remember_token']) && isset($pdo)) {
    $parts = explode(':', $_COOKIE['remember_token'], 2);
    if (count($parts) === 2) {
        $tokenHash = hash('sha256', $parts[1]);
        try {
            $stmt = $pdo->prepare('UPDATE sesi_login SET waktu_logout = NOW() WHERE id_pengguna = ? AND token_hash = ?');
            $stmt->execute([(int)$parts[0], $tokenHash]);
        } catch (Exception $e) {
            // Lanjutkan proses logout
        }
    }
}

// Hapus cookie ingat saya
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Hapus seluruh session
$_SESSION = [];
if (session_id() !== '' || isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
session_destroy();

header('Location: ' . $base . '');
exit;
