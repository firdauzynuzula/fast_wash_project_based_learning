<?php
// Endpoint status pesanan untuk polling realtime nota pelanggan
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$token = trim((string) ($_GET['t'] ?? ''));

if (!preg_match('/^[a-f0-9]{32}$/i', $token)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Token tidak valid']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT kode_pesanan, status, status_bayar
          FROM pesanan
         WHERE token_nota = :token
         ORDER BY id_pesanan ASC
    ");
    $stmt->execute(['token' => $token]);
    $pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$pesanan) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Pesanan tidak ditemukan']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'pesanan' => $pesanan
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Kesalahan server']);
}
