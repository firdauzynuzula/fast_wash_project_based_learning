<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function jsonKeluar(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonKeluar(['ok' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

csrf_check();

$nama    = trim($_POST['nama'] ?? '');
$telepon = trim($_POST['telepon'] ?? '');

if ($nama === '') {
    jsonKeluar(['ok' => false, 'message' => 'Nama lengkap wajib diisi.'], 422);
}
if ($telepon === '') {
    jsonKeluar(['ok' => false, 'message' => 'Nomor WhatsApp wajib diisi.'], 422);
}
if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $telepon)) {
    jsonKeluar(['ok' => false, 'message' => 'Format nomor WhatsApp tidak valid.'], 422);
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO pelanggan (nama, no_telepon, tanggal_daftar)
         VALUES (:nama, :telp, NOW())
         RETURNING id_pelanggan"
    );
    $stmt->execute(['nama' => $nama, 'telp' => $telepon]);
    $id = (int) $stmt->fetchColumn();

    jsonKeluar([
        'ok'   => true,
        'id'   => $id,
        'nama' => $nama,
        'telp' => $telepon,
    ]);
} catch (Exception $e) {
    jsonKeluar(['ok' => false, 'message' => 'Gagal menyimpan pelanggan: ' . $e->getMessage()], 500);
}