<?php
// Wajib di-require PERTAMA oleh setiap entry file di webroot.
session_start();

// Muat .env (KEY=VALUE per baris) jika ada — tanpa dependency.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

// $base = prefix relatif untuk URL browser ('./' di root, '../' di subfolder modul).
// Dihitung dari SCRIPT_FILENAME = ENTRY file (bukan file include),
// sehingga benar di semua kedalaman dan di subfolder install.
// Jika request lewat router index.php, $base sudah diset dari URL -> pakai itu.
if (!isset($base)) {
    $rootDir   = str_replace('\\', '/', dirname(__DIR__));
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
    $rel       = trim(substr($scriptDir, strlen($rootDir)), '/');
    $base      = $rel === '' ? './' : str_repeat('../', substr_count($rel, '/') + 1);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
// Halaman yang tidak butuh database (login default, dashboard) set $tanpaDb = true sebelum require bootstrap.
if (empty($tanpaDb)) {
    require_once __DIR__ . '/koneksi.php';   // menghasilkan $pdo

    // Fitur "Ingat Saya" (Remember Me): auto-login jika cookie valid
    if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_token']) && isset($pdo)) {
        $parts = explode(':', $_COOKIE['remember_token'], 2);
        if (count($parts) === 2) {
            [$userId, $token] = $parts;
            $tokenHash = hash('sha256', $token);

            try {
                $stmtSesi = $pdo->prepare('
                    SELECT s.id_pengguna, u.nama, u.role, u.email, u.is_active
                    FROM sesi_login s
                    JOIN v_login_identitas u ON u.id_pengguna = s.id_pengguna
                    WHERE s.id_pengguna = ?
                      AND s.token_hash = ?
                      AND s.waktu_kadaluarsa > NOW()
                      AND s.waktu_logout IS NULL
                      AND s.ingat_saya = true
                    LIMIT 1
                ');
                $stmtSesi->execute([(int)$userId, $tokenHash]);
                $userSesi = $stmtSesi->fetch();

                if ($userSesi && $userSesi['is_active']) {
                    $_SESSION['user_id'] = $userSesi['id_pengguna'];
                    $_SESSION['nama']    = $userSesi['nama'];
                    $_SESSION['role']    = $userSesi['role'];
                    $_SESSION['email']   = $userSesi['email'];
                } else {
                    setcookie('remember_token', '', time() - 3600, '/');
                }
            } catch (Exception $e) {
                // Jangan gagalkan bootstrap jika ada kendala pembacaan cookie
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
    }
}
