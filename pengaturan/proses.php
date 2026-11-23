<?php
/**
 * Fast Wash - Proses Tambah / Edit / Hapus Layanan.
 * Satu file untuk tiga rute (lihat $routes di index.php root):
 *   pengaturan/proses_tambah, pengaturan/proses_edit, pengaturan/hapus
 * Aksi ditentukan dari nama rute. Setelah selesai, kembali ke halaman Pengaturan
 * dan hasilnya ditampilkan sebagai modal.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/data_layanan.php';

// Hanya pemilik
if (!in_array($_SESSION['role'] ?? '', ['pemilik', 'admin'], true)) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

// Hanya lewat form (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $base . 'pengaturan');
    exit;
}

// TODO: verifikasi token CSRF (field "csrf") dengan fungsi bawaan project sebelum memproses.

$kembali = $base . 'pengaturan';
$aksi    = basename($route ?? '');   // proses_tambah | proses_edit | hapus

function flashLayanan(string $tipe, string $judul, string $pesan): void
{
    $_SESSION['flash_layanan'] = ['tipe' => $tipe, 'judul' => $judul, 'pesan' => $pesan];
}

layananSemua(); // pastikan data awal sudah ada
$daftar = &$_SESSION['layanan'];

$id        = (int) ($_POST['id'] ?? 0);
$nama      = trim($_POST['nama'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$harga     = (int) preg_replace('/\D/', '', $_POST['harga'] ?? '');
$aktif     = ($_POST['status'] ?? 'aktif') === 'aktif';

switch ($aksi) {
    case 'proses_tambah':
        if ($nama === '' || $harga <= 0) {
            flashLayanan('gagal', 'Gagal', 'Nama layanan dan harga wajib diisi.');
            break;
        }
        $daftar[] = [
            'id'        => $_SESSION['layanan_next_id']++,
            'nama'      => mb_substr($nama, 0, 100),
            'deskripsi' => mb_substr($deskripsi, 0, 255),
            'harga'     => $harga,
            'aktif'     => $aktif,
        ];
        flashLayanan('sukses', 'Berhasil', 'Layanan baru berhasil ditambahkan.');
        break;

    case 'proses_edit':
        if ($nama === '' || $harga <= 0) {
            flashLayanan('gagal', 'Gagal', 'Nama layanan dan harga wajib diisi.');
            break;
        }
        $ditemukan = false;
        foreach ($daftar as &$item) {
            if ((int) $item['id'] === $id) {
                $item['nama']      = mb_substr($nama, 0, 100);
                $item['deskripsi'] = mb_substr($deskripsi, 0, 255);
                $item['harga']     = $harga;
                $item['aktif']     = $aktif;
                $ditemukan = true;
                break;
            }
        }
        unset($item);
        if ($ditemukan) {
            flashLayanan('sukses', 'Berhasil', 'Perubahan berhasil disimpan.');
        } else {
            flashLayanan('gagal', 'Gagal', 'Layanan tidak ditemukan.');
        }
        break;

    case 'hapus':
        $sebelum = count($daftar);
        $daftar  = array_values(array_filter($daftar, fn($item) => (int) $item['id'] !== $id));
        if (count($daftar) < $sebelum) {
            flashLayanan('sukses', 'Berhasil', 'Layanan berhasil dihapus.');
        } else {
            flashLayanan('gagal', 'Gagal', 'Layanan tidak ditemukan.');
        }
        break;

    default:
        break;
}

header('Location: ' . $kembali);
exit;