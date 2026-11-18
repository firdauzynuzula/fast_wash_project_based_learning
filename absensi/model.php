<?php
/**
 * Fast Wash - Modul Absensi: Lapisan Model
 * Mengelola seluruh query PostgreSQL untuk entitas absensi dan log_aktifitas.
 */

if (!defined('ABSENSI_TIMEZONE_SET')) {
    date_default_timezone_set('Asia/Jakarta');
    define('ABSENSI_TIMEZONE_SET', true);
}

/**
 * Resolusi id_karyawan dari id_pengguna untuk pengguna dengan role karyawan.
 */
function absensi_ambil_id_karyawan(PDO $pdo, int $idPengguna): ?int
{
    if (!empty($_SESSION['id_karyawan']) && (int) $_SESSION['id_karyawan'] > 0) {
        return (int) $_SESSION['id_karyawan'];
    }

    if ($idPengguna <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id_karyawan FROM pengguna WHERE id_pengguna = :id AND role = 'karyawan' LIMIT 1");
    $stmt->execute([':id' => $idPengguna]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $_SESSION['id_karyawan'] = (int) $id;
        return (int) $id;
    }

    return null;
}

/**
 * Mengambil rekaman absensi hari ini untuk karyawan tertentu.
 */
function absensi_hari_ini(PDO $pdo, int $idKaryawan, ?string $tanggal = null): ?array
{
    $tanggal = $tanggal ?: date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM absensi WHERE id_karyawan = :k AND tanggal = :t LIMIT 1");
    $stmt->execute([
        ':k' => $idKaryawan,
        ':t' => $tanggal,
    ]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    return $data ?: null;
}

/**
 * Check-in otomatis idempoten saat karyawan login atau memulai hari kerja.
 * Menggunakan ON CONFLICT DO NOTHING agar aman dipanggil berulang kali.
 *
 * @return array{success: bool, is_new: bool, status: string, message: string}
 */
function absensi_checkin_otomatis(PDO $pdo, int $idKaryawan, ?int $idPengguna = null): array
{
    if ($idKaryawan <= 0) {
        return ['success' => false, 'is_new' => false, 'status' => '', 'message' => 'ID karyawan tidak valid.'];
    }

    $tanggalHariIni = date('Y-m-d');
    $waktuSekarang  = date('Y-m-d H:i:s');
    $jamSekarang    = date('H:i:s');

    // Batas jam masuk tepat waktu: 08:00:59 WIB
    $statusMasuk = ($jamSekarang <= '08:00:59') ? 'hadir' : 'terlambat';

    try {
        $sql = "INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, jam_pulang, status)
                VALUES (:k, :t, :jm, NULL, :s)
                ON CONFLICT (id_karyawan, tanggal) DO NOTHING
                RETURNING id_absensi, status, jam_masuk";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':k'  => $idKaryawan,
            ':t'  => $tanggalHariIni,
            ':jm' => $waktuSekarang,
            ':s'  => $statusMasuk,
        ]);

        $inserted = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($inserted) {
            // Baris baru berhasil dicatat -> catat ke log_aktifitas jika idPengguna tersedia
            if ($idPengguna && $idPengguna > 0) {
                try {
                    $logSql = "INSERT INTO log_aktifitas (id_pengguna, waktu, aksi, deskripsi)
                               VALUES (:u, NOW(), 'ABSENSI_MASUK', :d)";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([
                        ':u' => $idPengguna,
                        ':d' => 'Check-in presensi masuk tercatat otomatis pada pukul ' . date('H:i') . ' (Status: ' . ucfirst($statusMasuk) . ')',
                    ]);
                } catch (Throwable $e) {
                    error_log('Gagal mencatat log_aktifitas checkin: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'is_new'  => true,
                'status'  => $statusMasuk,
                'message' => 'Check-in otomatis berhasil tercatat pukul ' . date('H:i') . '.',
            ];
        }

        // Sudah ada catatan sebelumnya hari ini
        $existing = absensi_hari_ini($pdo, $idKaryawan, $tanggalHariIni);
        return [
            'success' => true,
            'is_new'  => false,
            'status'  => $existing['status'] ?? $statusMasuk,
            'message' => 'Presensi hari ini sudah tercatat sebelumnya.',
        ];
    } catch (Throwable $e) {
        error_log('Gagal menjalankan absensi_checkin_otomatis: ' . $e->getMessage());
        return [
            'success' => false,
            'is_new'  => false,
            'status'  => '',
            'message' => 'Terjadi kesalahan sistem saat mencatat presensi masuk.',
        ];
    }
}

/**
 * Pemrosesan Check-out mandiri oleh karyawan untuk mencatat jam_pulang.
 *
 * @return array{success: bool, message: string}
 */
function absensi_checkout(PDO $pdo, int $idKaryawan, ?int $idPengguna = null): array
{
    if ($idKaryawan <= 0) {
        return ['success' => false, 'message' => 'ID karyawan tidak valid.'];
    }

    $tanggalHariIni = date('Y-m-d');
    $waktuSekarang  = date('Y-m-d H:i:s');
    $today          = absensi_hari_ini($pdo, $idKaryawan, $tanggalHariIni);

    if (!$today || empty($today['jam_masuk'])) {
        return ['success' => false, 'message' => 'Anda belum memiliki catatan check-in hari ini.'];
    }

    if (!empty($today['jam_pulang'])) {
        return ['success' => false, 'message' => 'Anda sudah melakukan check-out hari ini.'];
    }

    try {
        // GREATEST(:jam_pulang, jam_masuk) menjaga constraint chk_absensi_jam
        $sql = "UPDATE absensi
                   SET jam_pulang = GREATEST(:jam_pulang::timestamp, jam_masuk)
                 WHERE id_absensi = :id
                   AND id_karyawan = :k
                   AND jam_pulang IS NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':jam_pulang' => $waktuSekarang,
            ':id'         => (int) $today['id_absensi'],
            ':k'          => $idKaryawan,
        ]);

        if ($stmt->rowCount() > 0) {
            if ($idPengguna && $idPengguna > 0) {
                try {
                    $logSql = "INSERT INTO log_aktifitas (id_pengguna, waktu, aksi, deskripsi)
                               VALUES (:u, NOW(), 'ABSENSI_PULANG', :d)";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([
                        ':u' => $idPengguna,
                        ':d' => 'Check-out presensi pulang tercatat pada pukul ' . date('H:i'),
                    ]);
                } catch (Throwable $e) {
                    error_log('Gagal mencatat log_aktifitas checkout: ' . $e->getMessage());
                }
            }

            return ['success' => true, 'message' => 'Check-out berhasil pukul ' . date('H:i') . '. Selamat beristirahat!'];
        }

        return ['success' => false, 'message' => 'Check-out tidak dapat diproses atau sudah tercatat sebelumnya.'];
    } catch (Throwable $e) {
        error_log('Gagal memproses absensi_checkout: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat memperbarui jam pulang.'];
    }
}

/**
 * Menghitung total data riwayat absensi karyawan untuk keperluan paginasi.
 */
function absensi_hitung_riwayat(PDO $pdo, int $idKaryawan, string $q = '', string $status = ''): int
{
    $where  = 'WHERE id_karyawan = :k';
    $params = [':k' => $idKaryawan];

    if ($q !== '') {
        $where .= ' AND LOWER(status) LIKE :q';
        $params[':q'] = '%' . strtolower($q) . '%';
    }

    $validStatus = ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'];
    if ($status !== '' && in_array(strtolower($status), $validStatus, true)) {
        $where .= ' AND LOWER(status) = :s';
        $params[':s'] = strtolower($status);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi $where");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Mengambil daftar riwayat absensi karyawan terurut dari tanggal terbaru.
 */
function absensi_daftar_riwayat(PDO $pdo, int $idKaryawan, string $q = '', string $status = '', int $limit = 7, int $offset = 0): array
{
    $where  = 'WHERE id_karyawan = :k';
    $params = [':k' => $idKaryawan];

    if ($q !== '') {
        $where .= ' AND LOWER(status) LIKE :q';
        $params[':q'] = '%' . strtolower($q) . '%';
    }

    $validStatus = ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'];
    if ($status !== '' && in_array(strtolower($status), $validStatus, true)) {
        $where .= ' AND LOWER(status) = :s';
        $params[':s'] = strtolower($status);
    }

    $sql = "SELECT * FROM absensi $where ORDER BY tanggal DESC, jam_masuk DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
