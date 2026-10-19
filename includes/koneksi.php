<?php
$config = require __DIR__ . '/config.php';

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $config['db_host'],
    $config['db_port'],
    $config['db_name'],
    $config['db_sslmode']
);

try {
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Aman dipakai lewat connection pooler (mis. Supabase) dan lokal.
        PDO::ATTR_EMULATE_PREPARES   => true,
    ]);
} catch (PDOException $e) {
    // Pesan asli memuat host/db/user — jangan tampilkan ke pengunjung.
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);
    exit($config['app_debug']
        ? 'Koneksi database gagal: ' . $e->getMessage()
        : 'Koneksi database gagal. Silakan hubungi administrator.');
}
