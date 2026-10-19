<?php
// Guard: require SETELAH bootstrap.php di setiap entry yang butuh login
// (semua kecuali auth/login, auth/proses_login, auth/register, auth/proses_register).
// Harus dipanggil sebelum output apa pun agar header() masih bisa.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'auth/login');
    exit;
}
