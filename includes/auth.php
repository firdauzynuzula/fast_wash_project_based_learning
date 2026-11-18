<?php
// Guard: require SETELAH bootstrap.php di setiap entry yang butuh login.
// Harus dipanggil sebelum output apa pun agar header() masih bisa.
$base = $base ?? './';

require_once __DIR__ . '/auth_db.php';

if (!isset($_SESSION['user_id'])) {
    if (!empty($pdo) && pulihkan_dari_cookie($pdo)) {
        // Sesi berhasil dipulihkan dari remember-me cookie
    } else {
        header('Location: ' . $base . '');
        exit;
    }
}
