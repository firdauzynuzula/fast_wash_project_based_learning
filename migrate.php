<?php
/**
 * Fast Wash - Database Migration Runner
 * 
 * Tool CLI untuk manajemen migrasi basis data PostgreSQL tanpa framework eksternal.
 * Menjalankan skrip SQL berurutan, mencatat riwayat di tabel `_migrations`,
 * dan mencegah konflik skema antar anggota tim.
 * 
 * Penggunaan:
 *   php migrate.php             Jalankan semua migrasi yang belum dieksekusi
 *   php migrate.php up          (Sama dengan di atas)
 *   php migrate.php status      Tampilkan status semua berkas migrasi
 *   php migrate.php create <nm> Buat berkas migrasi baru ber-timestamp
 *   php migrate.php baseline    Tandai semua berkas saat ini sebagai [APPLIED] tanpa mengeksekusi
 *   php migrate.php help        Tampilkan panduan bantuan
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Akses ditolak: Skrip ini hanya dapat dijalankan melalui CLI (terminal).\n");
}

// Set timezone ke Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi environment & koneksi database PDO
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/includes/bootstrap.php';

// Direktori berkas migrasi
$migrationsDir = __DIR__ . '/database/migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

// Inisialisasi tabel tracking _migrations jika belum ada
$pdo->exec("
    CREATE TABLE IF NOT EXISTS _migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INTEGER NOT NULL DEFAULT 1,
        applied_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
    );
");

// Parsing argumen CLI
$command = $argv[1] ?? 'up';
$arg2    = $argv[2] ?? null;

switch (strtolower($command)) {
    case 'up':
    case 'migrate':
        runMigrations($pdo, $migrationsDir);
        break;

    case 'status':
        showStatus($pdo, $migrationsDir);
        break;

    case 'create':
        createMigration($migrationsDir, $arg2);
        break;

    case 'baseline':
    case 'mark-applied':
        baselineMigrations($pdo, $migrationsDir);
        break;

    case 'help':
    case '--help':
    case '-h':
        showHelp();
        break;

    default:
        echo "\033[31m[ERROR]\033[0m Perintah '{$command}' tidak dikenali.\n";
        echo "Gunakan 'php migrate.php help' untuk melihat daftar perintah yang tersedia.\n";
        exit(1);
}

/**
 * 1. Menjalankan migrasi yang tertunda (PENDING)
 */
function runMigrations(PDO $pdo, string $dir): void
{
    echo "=========================================================\n";
    echo " FAST WASH - DATABASE MIGRATION RUNNER\n";
    echo "=========================================================\n";

    $allFiles = getMigrationFiles($dir);
    $applied  = getAppliedMigrations($pdo);

    $pending = array_diff($allFiles, array_keys($applied));

    if (empty($pending)) {
        echo "\033[32m[INFO]\033[0m Tidak ada migrasi baru. Basis data sudah up-to-date.\n\n";
        return;
    }

    $nextBatch = getNextBatchNumber($pdo);
    echo "\033[36m[START]\033[0m Memulai eksekusi " . count($pending) . " berkas migrasi (Batch {$nextBatch})...\n\n";

    $successCount = 0;

    foreach ($pending as $file) {
        $filePath = $dir . '/' . $file;
        $sql = file_get_contents($filePath);

        if ($sql === false || trim($sql) === '') {
            echo "  \033[33m[SKIP]\033[0m {$file} (berkas kosong)\n";
            continue;
        }

        echo "  > \033[34m[RUN]\033[0m Menjalankan: {$file}...";
        $start = microtime(true);

        try {
            $pdo->beginTransaction();

            // Eksekusi seluruh kueri dalam berkas migrasi
            $pdo->exec($sql);

            // Rekam ke tabel _migrations
            $stmt = $pdo->prepare("INSERT INTO _migrations (migration, batch, applied_at) VALUES (?, ?, NOW())");
            $stmt->execute([$file, $nextBatch]);

            $pdo->commit();
            $duration = round((microtime(true) - $start) * 1000, 2);
            echo " \033[32m[OK]\033[0m ({$duration} ms)\n";
            $successCount++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "\n  \033[31m[FAIL]\033[0m Gagal mengeksekusi migrasi: {$file}\n";
            echo "  Pesan Error: " . $e->getMessage() . "\n\n";
            echo "\033[31m[ABORT]\033[0m Proses migrasi dihentikan. Transaksi dibatalkan (Rollback).\n";
            exit(1);
        }
    }

    echo "\n\033[32m[SELESAI]\033[0m Sebanyak {$successCount} berkas migrasi berhasil diterapkan (Batch {$nextBatch}).\n\n";
}

/**
 * 2. Menampilkan tabel status migrasi
 */
function showStatus(PDO $pdo, string $dir): void
{
    echo "=========================================================\n";
    echo " FAST WASH - STATUS MIGRASI BASIS DATA\n";
    echo "=========================================================\n";

    $allFiles = getMigrationFiles($dir);
    $applied  = getAppliedMigrations($pdo);

    if (empty($allFiles)) {
        echo "Belum ada berkas migrasi di direktori 'database/migrations/'.\n";
        return;
    }

    $mask = "| %-4s | %-45s | %-10s | %-8s | %-20s |\n";
    $sep  = "+------+-----------------------------------------------+------------+----------+----------------------+\n";

    echo $sep;
    printf($mask, 'No', 'Nama Berkas Migrasi', 'Status', 'Batch', 'Waktu Diterapkan');
    echo $sep;

    $i = 1;
    $appliedCount = 0;
    $pendingCount = 0;

    foreach ($allFiles as $file) {
        $isApplied = isset($applied[$file]);
        if ($isApplied) {
            $statusStr = "\033[32mAPPLIED\033[0m   "; // pad space for ANSI
            $batchStr  = (string) $applied[$file]['batch'];
            $timeStr   = substr($applied[$file]['applied_at'], 0, 19);
            $appliedCount++;
        } else {
            $statusStr = "\033[33mPENDING\033[0m   ";
            $batchStr  = '-';
            $timeStr   = '-';
            $pendingCount++;
        }

        // Tampilkan nama file (potong jika terlalu panjang)
        $displayFile = strlen($file) > 45 ? substr($file, 0, 42) . '...' : $file;
        printf("| %-4d | %-45s | %s | %-8s | %-20s |\n", $i++, $displayFile, $statusStr, $batchStr, $timeStr);
    }

    echo $sep;
    echo "Total: " . count($allFiles) . " berkas | Diterapkan: {$appliedCount} | Tertunda: {$pendingCount}\n\n";
}

/**
 * 3. Membuat berkas migrasi baru dengan format timestamp
 */
function createMigration(string $dir, ?string $name): void
{
    if (!$name) {
        echo "\033[31m[ERROR]\033[0m Nama migrasi wajib diisi!\n";
        echo "Contoh penggunaan:\n";
        echo "  php migrate.php create tambah_kolom_diskon\n";
        exit(1);
    }

    // Bersihkan nama dari karakter non-alfanumerik
    $slug = preg_replace('/[^a-zA-Z0-9_]+/', '_', strtolower(trim($name)));
    $slug = trim($slug, '_');

    if (empty($slug)) {
        echo "\033[31m[ERROR]\033[0m Nama migrasi tidak valid.\n";
        exit(1);
    }

    $timestamp = date('Ymd_His');
    $fileName  = "{$timestamp}_{$slug}.sql";
    $filePath  = $dir . '/' . $fileName;

    if (file_exists($filePath)) {
        echo "\033[31m[ERROR]\033[0m Berkas migrasi {$fileName} sudah ada!\n";
        exit(1);
    }

    $template = <<<SQL
-- =====================================================================
-- FAST WASH MIGRATION: {$slug}
-- Dibuat pada: %s
-- =====================================================================

-- Tuliskan kueri DDL atau DML di bawah ini.
-- Contoh:
-- ALTER TABLE pesanan ADD COLUMN IF NOT EXISTS diskon NUMERIC(12,2) DEFAULT 0;

SQL;

    $content = sprintf($template, date('Y-m-d H:i:s'));
    file_put_contents($filePath, $content);

    echo "\033[32m[SUKSES]\033[0m Berkas migrasi baru berhasil dibuat:\n";
    echo "  -> database/migrations/{$fileName}\n\n";
    echo "Silakan edit berkas tersebut dan masukkan query SQL yang diinginkan,\n";
    echo "kemudian jalankan: php migrate.php\n\n";
}

/**
 * 4. Menandai semua migrasi yang ada saat ini sebagai [APPLIED] (Baseline)
 * Berguna saat database lokal sudah terisi tabel agar tidak dijalankan ulang.
 */
function baselineMigrations(PDO $pdo, string $dir): void
{
    echo "=========================================================\n";
    echo " FAST WASH - BASELINE MIGRATIONS\n";
    echo "=========================================================\n";

    $allFiles = getMigrationFiles($dir);
    $applied  = getAppliedMigrations($pdo);

    $unapplied = array_diff($allFiles, array_keys($applied));

    if (empty($unapplied)) {
        echo "\033[32m[INFO]\033[0m Semua berkas migrasi saat ini sudah tercatat sebagai [APPLIED].\n\n";
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO _migrations (migration, batch, applied_at) VALUES (?, 1, NOW()) ON CONFLICT (migration) DO NOTHING");

    $count = 0;
    foreach ($unapplied as $file) {
        $stmt->execute([$file]);
        echo "  [BASELINE] Menandai: {$file} sebagai APPLIED\n";
        $count++;
    }

    echo "\n\033[32m[SUKSES]\033[0m Sebanyak {$count} berkas migrasi berhasil ditandai sebagai [APPLIED] (Batch 1).\n";
    echo "Database lokal Anda sekarang telah sinkron dengan seluruh riwayat migrasi dasar.\n\n";
}

/**
 * 5. Menampilkan panduan bantuan
 */
function showHelp(): void
{
    echo <<<HELP
=========================================================
 FAST WASH - PANDUAN PENGGUNAAN MIGRATION RUNNER
=========================================================

Perintah yang tersedia:

1. php migrate.php (atau php migrate.php up)
   Menjalankan seluruh berkas migrasi SQL yang berstatus PENDING secara berurutan.
   Setiap berkas dieksekusi di dalam transaksi database yang aman.

2. php migrate.php status
   Melihat tabel status semua berkas migrasi (APPLIED vs PENDING),
   nomor batch, dan tanggal eksekusi.

3. php migrate.php create <nama_migrasi>
   Membuat berkas migrasi SQL baru di folder database/migrations/
   dengan format: YYYYMMDD_HHMMSS_<nama>.sql.
   Contoh: php migrate.php create tambah_kolom_poin_pelanggan

4. php migrate.php baseline
   Menandai seluruh berkas migrasi yang ada saat ini sebagai APPLIED
   tanpa mengeksekusi kueri SQL-nya. Digunakan jika database lokal sudah
   memiliki tabel lengkap agar tidak terjadi konflik/duplikasi.

5. php migrate.php help
   Menampilkan panduan ini.

HELP;
    echo "\n";
}

/**
 * Mengambil daftar nama berkas migrasi berurutan
 */
function getMigrationFiles(string $dir): array
{
    $files = scandir($dir);
    $migrations = [];

    foreach ($files as $f) {
        if (str_ends_with($f, '.sql')) {
            $migrations[] = $f;
        }
    }

    sort($migrations);
    return $migrations;
}

/**
 * Mengambil riwayat migrasi yang sudah tercatat di database
 */
function getAppliedMigrations(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT migration, batch, applied_at FROM _migrations ORDER BY id ASC");
    $applied = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $applied[$row['migration']] = $row;
    }
    return $applied;
}

/**
 * Mendapatkan nomor batch berikutnya
 */
function getNextBatchNumber(PDO $pdo): int
{
    $val = $pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM _migrations")->fetchColumn();
    return (int) $val;
}
