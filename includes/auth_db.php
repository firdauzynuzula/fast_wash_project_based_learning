<?php
/**
 * Fast Wash - Autentikasi Berbasis Database PostgreSQL
 */

/**
 * Normalisasi nomor telepon (membersihkan spasi/tanda hubung, mengubah format +62/62 menjadi 0).
 */
function normalisasi_telepon(string $tel): string
{
    $clean = preg_replace('/[^0-9]/', '', $tel);
    if (str_starts_with($clean, '62')) {
        $clean = '0' . substr($clean, 2);
    }
    return $clean;
}

/**
 * Cari pengguna berdasarkan email atau nomor telepon dari view v_login_identitas.
 */
function cari_pengguna_login(PDO $pdo, string $identitas, string $role): ?array
{
    if (!in_array($role, ['pemilik', 'karyawan'], true)) {
        return null;
    }

    $tel = normalisasi_telepon($identitas);

    $sql = "SELECT id_pengguna, email, no_telepon, password_hash, role, is_active, nama
              FROM v_login_identitas
             WHERE role = :role
               AND (LOWER(email) = LOWER(:identitas) OR no_telepon = :telepon)
             LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':role'      => $role,
        ':identitas' => trim($identitas),
        ':telepon'   => $tel !== '' ? $tel : trim($identitas),
    ]);

    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Verifikasi login dengan proteksi timing-attack dan validasi status aktif.
 */
function verifikasi_login(PDO $pdo, string $identitas, string $password, string $role): ?array
{
    $user = cari_pengguna_login($pdo, $identitas, $role);

    // Dummy hash agar password_verify selalu dijalankan (mencegah timing attack)
    $dummyHash = '$2y$12$7AW2RfQtzAPClaI6NZSAA.1Tiich9gYavL1CJGIA1f5BWnCAWs7z6';
    $hashToCheck = $user['password_hash'] ?? $dummyHash;

    $passwordOk = password_verify($password, $hashToCheck);

    if (!$user || !$passwordOk) {
        return null;
    }

    if (empty($user['is_active'])) {
        return null;
    }

    return $user;
}

/**
 * Mulai sesi pengguna dan catat ke tabel sesi_login (+ remember-me cookie opsional).
 */
function mulai_sesi(PDO $pdo, array $user, bool $ingat = false): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id_pengguna'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['email']   = $user['email'];

    // Buat token sesi acak (32 bytes)
    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    // Durasi kadaluarsa: 30 hari jika "ingat saya", 1 hari jika tidak
    $durasiHari = $ingat ? 30 : 1;
    $kadaluarsa = date('Y-m-d H:i:s', time() + ($durasiHari * 86400));

    $sql = "INSERT INTO sesi_login (id_pengguna, token_hash, waktu_login, waktu_kadaluarsa, ingat_saya)
            VALUES (:id_pengguna, :token_hash, NOW(), :kadaluarsa, :ingat)
            RETURNING id_sesion";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_pengguna' => $user['id_pengguna'],
        ':token_hash'  => $tokenHash,
        ':kadaluarsa'  => $kadaluarsa,
        ':ingat'       => $ingat ? 'true' : 'false',
    ]);

    $idSesion = (int) $stmt->fetchColumn();
    $_SESSION['id_sesion'] = $idSesion;

    // Jika role karyawan, simpan id_karyawan dan lakukan check-in otomatis
    if ($user['role'] === 'karyawan') {
        require_once dirname(__DIR__) . '/absensi/model.php';
        $idKaryawan = absensi_ambil_id_karyawan($pdo, (int) $user['id_pengguna']);
        if ($idKaryawan) {
            $_SESSION['id_karyawan'] = $idKaryawan;
            absensi_checkin_otomatis($pdo, $idKaryawan, (int) $user['id_pengguna']);
        }
    }

    // Pasang cookie Remember-Me jika dicentang
    if ($ingat) {
        $cookieValue  = $idSesion . ':' . $rawToken;
        $cookieExpire = time() + ($durasiHari * 86400);

        setcookie('fw_remember', $cookieValue, [
            'expires'  => $cookieExpire,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Pulihkan sesi pengguna dari cookie "fw_remember" jika session PHP kosong.
 */
function pulihkan_dari_cookie(PDO $pdo): bool
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (empty($_COOKIE['fw_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['fw_remember'], 2);
    if (count($parts) !== 2) {
        hapus_cookie_remember();
        return false;
    }

    [$idSesion, $rawToken] = $parts;
    $idSesion = (int) $idSesion;
    if ($idSesion <= 0) {
        hapus_cookie_remember();
        return false;
    }

    $sql = "SELECT s.id_sesion, s.token_hash, s.waktu_kadaluarsa, s.ingat_saya,
                   v.id_pengguna, v.email, v.role, v.is_active, v.nama
              FROM sesi_login s
              JOIN v_login_identitas v ON v.id_pengguna = s.id_pengguna
             WHERE s.id_sesion = :id_sesion
               AND s.waktu_logout IS NULL
               AND s.waktu_kadaluarsa > NOW()
               AND s.ingat_saya = TRUE
               AND v.is_active = TRUE
               AND v.role IN ('pemilik', 'karyawan')
             LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_sesion' => $idSesion]);
    $sesi = $stmt->fetch();

    if (!$sesi) {
        hapus_cookie_remember();
        return false;
    }

    // Verifikasi hash token
    $computedHash = hash('sha256', $rawToken);
    if (!hash_equals($sesi['token_hash'], $computedHash)) {
        hapus_cookie_remember();
        return false;
    }

    // Token valid -> isi sesi
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $sesi['id_pengguna'];
    $_SESSION['nama']      = $sesi['nama'];
    $_SESSION['role']      = $sesi['role'];
    $_SESSION['email']     = $sesi['email'];
    $_SESSION['id_sesion'] = (int) $sesi['id_sesion'];

    // Jika role karyawan, simpan id_karyawan dan lakukan check-in otomatis pada hari baru
    if ($sesi['role'] === 'karyawan') {
        require_once dirname(__DIR__) . '/absensi/model.php';
        $idKaryawan = absensi_ambil_id_karyawan($pdo, (int) $sesi['id_pengguna']);
        if ($idKaryawan) {
            $_SESSION['id_karyawan'] = $idKaryawan;
            absensi_checkin_otomatis($pdo, $idKaryawan, (int) $sesi['id_pengguna']);
        }
    }

    return true;
}

/**
 * Hapus cookie remember me.
 */
function hapus_cookie_remember(): void
{
    if (isset($_COOKIE['fw_remember'])) {
        setcookie('fw_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE['fw_remember']);
    }
}

/**
 * Akhiri sesi: update sesi_login waktu_logout, hapus cookie, destroy session.
 */
function akhiri_sesi(PDO $pdo): void
{
    if (!empty($_SESSION['id_sesion'])) {
        $stmt = $pdo->prepare("UPDATE sesi_login SET waktu_logout = NOW() WHERE id_sesion = :id");
        $stmt->execute([':id' => $_SESSION['id_sesion']]);
    }

    hapus_cookie_remember();
    $_SESSION = [];
    if (session_id() !== '' || headers_sent() === false) {
        session_destroy();
    }
}

/**
 * Daftarkan staf karyawan baru secara atomik ke tabel karyawan dan pengguna.
 *
 * @return array{success: bool, message: string, id_pengguna?: int, id_karyawan?: int}
 */
function daftarkan_karyawan(PDO $pdo, string $nama, string $email, string $telepon, string $password): array
{
    $nama  = trim($nama);
    $email = strtolower(trim($email));
    $tel   = normalisasi_telepon($telepon);

    if ($nama === '' || $email === '' || $tel === '' || $password === '') {
        return ['success' => false, 'message' => 'Semua kolom wajib diisi.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email tidak valid.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Kata sandi minimal harus 8 karakter.'];
    }

    // 1. Cek apakah email sudah terdaftar di tabel pengguna
    $stmtCekEmail = $pdo->prepare("SELECT 1 FROM pengguna WHERE LOWER(email) = :email LIMIT 1");
    $stmtCekEmail->execute([':email' => $email]);
    if ($stmtCekEmail->fetch()) {
        return ['success' => false, 'message' => 'Alamat email sudah terdaftar. Silakan gunakan email lain atau masuk.'];
    }

    // 2. Cek apakah nomor telepon sudah terdaftar di tabel karyawan
    $stmtCekTel = $pdo->prepare("SELECT 1 FROM karyawan WHERE no_telepon = :tel LIMIT 1");
    $stmtCekTel->execute([':tel' => $tel]);
    if ($stmtCekTel->fetch()) {
        return ['success' => false, 'message' => 'Nomor WhatsApp / telepon sudah terdaftar sebagai staf karyawan.'];
    }

    // 3. Simpan data secara atomik menggunakan transaksi DB
    try {
        $pdo->beginTransaction();

        // Simpan profil ke tabel karyawan
        $stmtK = $pdo->prepare("INSERT INTO karyawan (nama, no_telepon, status_aktif) VALUES (:nama, :telepon, TRUE) RETURNING id_karyawan");
        $stmtK->execute([
            ':nama'    => $nama,
            ':telepon' => $tel,
        ]);
        $idKaryawan = (int) $stmtK->fetchColumn();

        // Enkripsi kata sandi dengan bcrypt
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Simpan akun ke tabel pengguna dengan role = 'karyawan'
        $stmtU = $pdo->prepare("INSERT INTO pengguna (id_karyawan, email, password_hash, role, is_active, waktu_dibuat, waktu_setuju_ketentuan)
                                VALUES (:id_karyawan, :email, :password_hash, 'karyawan', TRUE, NOW(), NOW())
                                RETURNING id_pengguna");
        $stmtU->execute([
            ':id_karyawan'   => $idKaryawan,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
        ]);
        $idPengguna = (int) $stmtU->fetchColumn();

        $pdo->commit();

        return [
            'success'     => true,
            'message'     => 'Pendaftaran akun karyawan berhasil! Silakan masuk dengan akun baru Anda.',
            'id_pengguna' => $idPengguna,
            'id_karyawan' => $idKaryawan,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Gagal mendaftarkan karyawan: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan sistem saat menyimpan data pendaftaran. Silakan coba lagi.',
        ];
    }
}

/**
 * Buat token reset kata sandi untuk pengguna (pemilik / karyawan) aktif.
 * Menyimpan hash SHA-256 ke tabel reset_password dengan masa berlaku 1 jam.
 *
 * @return array{plain_token: string, id_pengguna: int, nama: string, email: string, role: string, waktu_kadaluarsa: string}|null
 */
function buat_token_reset_password(PDO $pdo, string $email): ?array
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    // Cari pengguna aktif pemilik atau karyawan dari view identitas
    $sqlUser = "SELECT id_pengguna, email, role, is_active, nama
                  FROM v_login_identitas
                 WHERE LOWER(email) = LOWER(:email)
                   AND role IN ('pemilik', 'karyawan')
                   AND is_active = TRUE
                 LIMIT 1";

    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute([':email' => $email]);
    $user = $stmtUser->fetch();

    if (!$user) {
        return null;
    }

    try {
        // Buat token 32-byte acak
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $plainToken);

        $sqlInsert = "INSERT INTO reset_password (id_pengguna, token_hash, waktu_dibuat, waktu_kadaluarsa)
                      VALUES (:id_pengguna, :token_hash, NOW(), NOW() + INTERVAL '1 hour')
                      RETURNING id_reset_password, waktu_kadaluarsa";

        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':id_pengguna' => (int) $user['id_pengguna'],
            ':token_hash'  => $tokenHash,
        ]);

        $row = $stmtInsert->fetch();

        return [
            'plain_token'      => $plainToken,
            'id_pengguna'      => (int) $user['id_pengguna'],
            'nama'             => (string) $user['nama'],
            'email'            => (string) $user['email'],
            'role'             => (string) $user['role'],
            'waktu_kadaluarsa' => (string) ($row['waktu_kadaluarsa'] ?? ''),
        ];
    } catch (Throwable $e) {
        error_log("Gagal membuat token reset password: " . $e->getMessage());
        return null;
    }
}

/**
 * Validasi token reset kata sandi dari database.
 * Memastikan token belum kedaluwarsa (< 1 jam), belum pernah dipakai (waktu_dipakai IS NULL),
 * dan pengguna pemilik/karyawan berstatus aktif.
 *
 * @return array{id_reset_password: int, id_pengguna: int, email: string, role: string, nama: string}|null
 */
function validasi_token_reset(PDO $pdo, string $token): ?array
{
    $token = trim($token);
    if ($token === '') {
        return null;
    }

    $tokenHash = hash('sha256', $token);

    $sql = "SELECT r.id_reset_password, r.id_pengguna, r.waktu_kadaluarsa, r.waktu_dipakai,
                   u.email, u.role, u.is_active,
                   v.nama
              FROM reset_password r
              JOIN pengguna u ON u.id_pengguna = r.id_pengguna
              LEFT JOIN v_login_identitas v ON v.id_pengguna = u.id_pengguna
             WHERE r.token_hash = :hash
               AND r.waktu_dipakai IS NULL
               AND r.waktu_kadaluarsa > NOW()
               AND u.is_active = TRUE
               AND u.role IN ('pemilik', 'karyawan')
             LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hash' => $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    return [
        'id_reset_password' => (int) $row['id_reset_password'],
        'id_pengguna'      => (int) $row['id_pengguna'],
        'email'            => (string) $row['email'],
        'role'             => (string) $row['role'],
        'nama'             => (string) ($row['nama'] ?? $row['email']),
    ];
}

/**
 * Perbarui kata sandi akun via token reset secara atomik:
 * 1. Mengubah password_hash di tabel pengguna.
 * 2. Menandai waktu_dipakai = NOW() pada token di tabel reset_password (single-use).
 * 3. Memutuskan seluruh sesi aktif lama di tabel sesi_login.
 */
function perbarui_kata_sandi_reset(PDO $pdo, string $token, string $passwordBaru): bool
{
    if (strlen($passwordBaru) < 8) {
        return false;
    }

    $tokenData = validasi_token_reset($pdo, $token);
    if (!$tokenData) {
        return false;
    }

    $idPengguna      = $tokenData['id_pengguna'];
    $idResetPassword = $tokenData['id_reset_password'];
    $newHash         = password_hash($passwordBaru, PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        // 1. Update password di tabel pengguna
        $stmtUser = $pdo->prepare("UPDATE pengguna SET password_hash = :hash WHERE id_pengguna = :id");
        $stmtUser->execute([
            ':hash' => $newHash,
            ':id'   => $idPengguna,
        ]);

        // 2. Tandai token sudah digunakan (single-use)
        $stmtReset = $pdo->prepare("UPDATE reset_password SET waktu_dipakai = NOW() WHERE id_reset_password = :id");
        $stmtReset->execute([':id' => $idResetPassword]);

        // 3. Putuskan semua sesi aktif lama di tabel sesi_login
        $stmtSesi = $pdo->prepare("UPDATE sesi_login SET waktu_logout = NOW() WHERE id_pengguna = :id AND waktu_logout IS NULL");
        $stmtSesi->execute([':id' => $idPengguna]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Gagal memperbarui kata sandi via token: " . $e->getMessage());
        return false;
    }
}
