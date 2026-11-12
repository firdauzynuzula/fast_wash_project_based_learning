<?php
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Menghasilkan URL aset dengan query version berbasis waktu modifikasi file (cache busting).
 * Memastikan peramban dan CDN (seperti Cloudflare) selalu mengunduh aset terbaru saat ada perubahan.
 */
function asset_url(string $path): string
{
    global $base;
    $prefix = $base ?? './';
    $cleanPath = ltrim($path, '/');
    $fullPath = dirname(__DIR__) . '/' . $cleanPath;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : '1.0';

    return $prefix . $cleanPath . '?v=' . $version;
}

/**
 * Simpan token reset password di sesi dan server-side cache (temporary file).
 * Ini mencegah token invalid saat link diklik dari aplikasi eksternal (seperti Gmail)
 * di mana browser belum sempat menyertakan cookie PHPSESSID pada first-click.
 */
function simpan_reset_token(string $email, string $token, int $kadaluarsa): void
{
    $data = [
        'email'      => $email,
        'token'      => $token,
        'kadaluarsa' => $kadaluarsa,
    ];

    // 1. Simpan di Sesi PHP
    $_SESSION['dummy_reset'] = $data;

    // 2. Simpan di cache server agar kebal cross-site cookie drop dari Gmail
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fastwash_reset_tokens.json';
    $tokens = is_file($cacheFile) ? (json_decode(file_get_contents($cacheFile), true) ?: []) : [];

    // Bersihkan token yang sudah expired
    $tokens = array_filter($tokens, fn($item) => ($item['kadaluarsa'] ?? 0) > time());

    $tokens[$token] = $data;
    file_put_contents($cacheFile, json_encode($tokens), LOCK_EX);
}

/**
 * Mengambil dan memvalidasi token reset password.
 */
function ambil_reset_token(string $token): ?array
{
    if (empty($token)) {
        return null;
    }

    // 1. Cek di sesi saat ini jika cookie terbawa
    if (isset($_SESSION['dummy_reset'])) {
        $sessData = $_SESSION['dummy_reset'];
        if (hash_equals($sessData['token'] ?? '', $token)) {
            if (time() <= ($sessData['kadaluarsa'] ?? 0)) {
                return $sessData;
            }
        }
    }

    // 2. Fallback: Ambil dari cache server jika diklik dari Gmail (cross-site first click)
    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fastwash_reset_tokens.json';
    if (is_file($cacheFile)) {
        $tokens = json_decode(file_get_contents($cacheFile), true) ?: [];
        if (isset($tokens[$token])) {
            $data = $tokens[$token];
            if (time() <= ($data['kadaluarsa'] ?? 0)) {
                // Sinkronkan ke sesi tab yang baru dibuka ini
                $_SESSION['dummy_reset'] = $data;
                return $data;
            }
        }
    }

    return null;
}

/**
 * Hapus token setelah digunakan (single-use).
 */
function hapus_reset_token(string $token): void
{
    unset($_SESSION['dummy_reset']);

    $cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fastwash_reset_tokens.json';
    if (is_file($cacheFile)) {
        $tokens = json_decode(file_get_contents($cacheFile), true) ?: [];
        if (isset($tokens[$token])) {
            unset($tokens[$token]);
            file_put_contents($cacheFile, json_encode($tokens), LOCK_EX);
        }
    }
}
