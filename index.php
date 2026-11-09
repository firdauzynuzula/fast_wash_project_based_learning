<?php
// Front controller (router sederhana).
// .htaccess mengarahkan SEMUA request yang bukan file asli (css/js/gambar) ke file ini,
// lalu router memilih file tujuan berdasarkan URL.

// Daftar route: URL (tanpa .php, tanpa slash di awal/akhir) => file tujuan.
// Hanya URL yang terdaftar di sini yang bisa diakses; selain itu 404.
// Tambah halaman baru = tambah 1 baris di sini.
$routes = [
    ''                      => 'auth/login.php',
    'index'                 => 'auth/login.php',
    'dashboard'             => 'dashboard/index.php',
    'dashboard/index'       => 'dashboard/index.php',

    'auth/login'            => 'auth/login.php',
    'auth/proses_login'     => 'auth/proses_login.php',
    'auth/register'         => 'auth/register.php',
    'auth/proses_register'  => 'auth/proses_register.php',
    'auth/logout'           => 'auth/logout.php',

    'pelanggan/list'          => 'pelanggan/list.php',
    'pelanggan/tambah'        => 'pelanggan/tambah.php',
    'pelanggan/proses_tambah' => 'pelanggan/proses_tambah.php',
    'pelanggan/edit'          => 'pelanggan/edit.php',
    'pelanggan/proses_edit'   => 'pelanggan/proses_edit.php',
    'pelanggan/hapus'         => 'pelanggan/hapus.php',

    'layanan/list'          => 'layanan/list.php',
    'layanan/tambah'        => 'layanan/tambah.php',
    'layanan/proses_tambah' => 'layanan/proses_tambah.php',
    'layanan/edit'          => 'layanan/edit.php',
    'layanan/proses_edit'   => 'layanan/proses_edit.php',
    'layanan/hapus'         => 'layanan/hapus.php',

    'transaksi/tambah'        => 'transaksi/tambah.php',
    'transaksi/proses_tambah' => 'transaksi/proses_tambah.php',
    'transaksi/status'        => 'transaksi/status.php',
    'transaksi/proses_status' => 'transaksi/proses_status.php',
    'transaksi/riwayat'       => 'transaksi/riwayat.php',
];

// Path URL relatif terhadap folder aplikasi (mendukung install di subfolder),
// mis. http://localhost/fast-wash-pbl/pelanggan/list?id=1 -> "/pelanggan/list".
$uri    = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
// Apache: SCRIPT_NAME = /<subfolder>/index.php. php -S: SCRIPT_NAME tidak andal, app selalu di root.
$appDir = PHP_SAPI === 'cli-server' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$path   = ($appDir !== '' && str_starts_with($uri, $appDir . '/')) ? substr($uri, strlen($appDir)) : $uri;

// Server bawaan PHP (`php -S localhost:8000 index.php`) tidak membaca .htaccess:
// biarkan file statis di assets/ disajikan langsung.
if (PHP_SAPI === 'cli-server' && str_starts_with($path, '/assets/') && is_file(__DIR__ . $path)) {
    return false;
}

$route = trim($path, '/');

// $base = prefix relatif ke root aplikasi untuk href/src di view
// ('./' untuk /index, '../' untuk /pelanggan/list, dst). Dipakai bootstrap, header, auth.
$depth = substr_count(ltrim($path, '/'), '/');
$base  = $depth === 0 ? './' : str_repeat('../', $depth);

if (!isset($routes[$route])) {
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>404 | Fast Wash</title></head>
<body style="font-family:sans-serif;text-align:center;padding:3rem">
    <h1>404 - Halaman tidak ditemukan</h1>
    <p><code><?php echo htmlspecialchars($route, ENT_QUOTES, 'UTF-8'); ?></code> tidak terdaftar.</p>
    <p><a href="<?php echo $base; ?>index">Kembali ke Beranda</a></p>
</body>
</html>
<?php
    exit;
}

// Sengaja di scope global (bukan di dalam fungsi) agar $base dkk. terlihat oleh halaman tujuan.
require __DIR__ . '/' . $routes[$route];
