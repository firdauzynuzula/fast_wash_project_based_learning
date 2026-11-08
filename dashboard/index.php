<?php
$title = 'Dashboard';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php'; // Guard: jika belum login -> redirect ke login

$role = $_SESSION['role'] ?? 'karyawan';
$nama = $_SESSION['nama'] ?? 'Pengguna';

// Ambil ringkasan jika database tersedia
$ringkasan = null;
if (isset($pdo)) {
    try {
        $ringkasan = $pdo->query('SELECT * FROM v_dasbor_hari_ini')->fetch();
    } catch (Exception $e) {
        $ringkasan = null;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header sambutan -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-[#1668dc]">Selamat datang kembali,</p>
            <h1 class="text-2xl font-extrabold text-[#16305b]"><?php echo e($nama); ?></h1>
            <p class="text-sm text-gray-500 mt-1">Anda masuk sebagai <span class="capitalize font-medium text-gray-800"><?php echo e($role); ?></span> di Sistem Manajemen Fast Wash.</p>
        </div>
        <div>
            <a href="<?php echo $base; ?>transaksi/tambah" class="inline-flex items-center gap-2 bg-[#1668dc] hover:bg-[#1259c4] text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-xs">
                <span>+ Pesanan Baru</span>
            </a>
        </div>
    </div>

    <!-- Kartu Statistik (KPI) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold uppercase text-gray-400 tracking-wider">Pesanan Hari Ini</span>
            <p class="text-2xl font-extrabold text-[#16305b] mt-2"><?php echo e((string)($ringkasan['total_pesanan_hari_ini'] ?? '0')); ?></p>
            <p class="text-xs text-gray-500 mt-1">Total cucian masuk</p>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold uppercase text-gray-400 tracking-wider">Pendapatan Hari Ini</span>
            <p class="text-2xl font-extrabold text-[#16305b] mt-2">Rp <?php echo number_format((float)($ringkasan['pendapatan_hari_ini'] ?? 0), 0, ',', '.'); ?></p>
            <p class="text-xs text-gray-500 mt-1">Dari transaksi valid</p>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold uppercase text-gray-400 tracking-wider">Pelanggan Aktif</span>
            <p class="text-2xl font-extrabold text-[#16305b] mt-2"><?php echo e((string)($ringkasan['pelanggan_aktif'] ?? '0')); ?></p>
            <p class="text-xs text-gray-500 mt-1">Dalam 30 hari terakhir</p>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold uppercase text-gray-400 tracking-wider">Siap Diambil</span>
            <p class="text-2xl font-extrabold text-[#1668dc] mt-2"><?php echo e((string)($ringkasan['siap_diambil'] ?? '0')); ?></p>
            <p class="text-xs text-gray-500 mt-1">Cucian sudah selesai</p>
        </div>
    </div>

    <!-- Navigasi Modul Cepat -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
        <h2 class="text-lg font-bold text-[#16305b] mb-4">Akses Cepat Modul</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?php echo $base; ?>pelanggan/list" class="p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition flex flex-col items-center text-center">
                <span class="w-10 h-10 rounded-full bg-blue-100 text-[#1668dc] flex items-center justify-center font-bold mb-2">👥</span>
                <span class="text-sm font-semibold text-gray-800">Data Pelanggan</span>
                <span class="text-xs text-gray-400 mt-0.5">Kelola daftar pelanggan</span>
            </a>

            <a href="<?php echo $base; ?>layanan/list" class="p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition flex flex-col items-center text-center">
                <span class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold mb-2">🧺</span>
                <span class="text-sm font-semibold text-gray-800">Katalog Layanan</span>
                <span class="text-xs text-gray-400 mt-0.5">Tarif cuci &amp; setrika</span>
            </a>

            <a href="<?php echo $base; ?>transaksi/status" class="p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition flex flex-col items-center text-center">
                <span class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold mb-2">⏳</span>
                <span class="text-sm font-semibold text-gray-800">Status Cucian</span>
                <span class="text-xs text-gray-400 mt-0.5">Pantau &amp; ubah progres</span>
            </a>

            <a href="<?php echo $base; ?>transaksi/riwayat" class="p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition flex flex-col items-center text-center">
                <span class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold mb-2">📋</span>
                <span class="text-sm font-semibold text-gray-800">Riwayat Pesanan</span>
                <span class="text-xs text-gray-400 mt-0.5">Semua riwayat transaksi</span>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
