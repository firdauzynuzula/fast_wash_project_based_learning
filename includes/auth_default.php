<?php
// Kredensial admin default (tanpa database).
// Password TIDAK disimpan plaintext: nilai di bawah adalah hasil
// password_hash('admin123', PASSWORD_DEFAULT) dan diverifikasi dengan password_verify().
const ADMIN_DEFAULT_USERNAME = 'admin';
const ADMIN_DEFAULT_HASH     = '$2y$10$BXjuWQ5gnIPOqhpIjIM7be5X0bvq9tojg/iSwbpnzmzEpV2Ih3OKa';

function cek_login_default(string $username, string $password): bool
{
    // password_verify selalu dijalankan agar waktu respons tidak membocorkan username valid.
    $passwordOk = password_verify($password, ADMIN_DEFAULT_HASH);
    $usernameOk = hash_equals(ADMIN_DEFAULT_USERNAME, $username);

    return $usernameOk && $passwordOk;
}
