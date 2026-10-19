<?php
$title = 'Beranda';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth.php';

// TODO: $totalPelanggan = $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
// TODO: $totalLayanan   = $pdo->query('SELECT COUNT(*) FROM layanan')->fetchColumn();
// TODO: $cucianAktif    = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status != 'diambil'")->fetchColumn();
$totalPelanggan = 0;
$totalLayanan = 0;
$cucianAktif = 0;

require_once __DIR__ . '/includes/header.php';
?>
        <section class="mb-8">
            <h1 class="text-2xl font-bold mb-2">Selamat Datang di Fast Wash</h1>
            <p class="text-gray-600">Aplikasi manajemen laundry: pelanggan, layanan, dan transaksi cucian.</p>
        </section>

        <!-- Form pencarian (dari dashboard.html) — TODO: arahkan ke halaman yang sesuai,
             mis. transaksi/riwayat atau pelanggan/list, lalu proses $_GET['search'] -->
        <form class="max-w-md mx-auto mb-8" action="transaksi/riwayat" method="get">
            <label for="search" class="block mb-2.5 text-sm font-medium text-heading sr-only">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <svg class="w-4 h-4 text-body" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg>
                </div>
                <input type="search" id="search" name="search" class="block w-full p-3 ps-9 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body" placeholder="Cari transaksi / pelanggan" required />
                <button type="submit" class="absolute end-1.5 bottom-1.5 text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded text-xs px-3 py-1.5 focus:outline-none">Search</button>
            </div>
        </form>

        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <article class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-medium text-gray-500">Total Pelanggan</h2>
                <p class="text-3xl font-bold"><?php echo $totalPelanggan; ?></p>
            </article>
            <article class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-medium text-gray-500">Total Layanan</h2>
                <p class="text-3xl font-bold"><?php echo $totalLayanan; ?></p>
            </article>
            <article class="bg-white rounded-lg shadow p-5">
                <h2 class="text-sm font-medium text-gray-500">Cucian Aktif</h2>
                <p class="text-3xl font-bold"><?php echo $cucianAktif; ?></p>
            </article>
        </section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
