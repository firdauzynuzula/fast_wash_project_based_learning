<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Ambil data pesanan
$stmt = $pdo->prepare("
    SELECT p.*, pl.nama AS nama_pelanggan
      FROM pesanan p
      JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
     WHERE p.id_pesanan = :id
");
$stmt->execute(['id' => $id]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    header('Location: ' . $base . 'pesanan');
    exit;
}

// Proses penghapusan (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Hapus pesanan (detail_pesanan akan ikut terhapus karena CASCADE)
    $stmtDelete = $pdo->prepare("DELETE FROM pesanan WHERE id_pesanan = :id");
    $stmtDelete->execute(['id' => $id]);

    $_SESSION['flash_sukses'] = "Pesanan {$pesanan['kode_pesanan']} berhasil dihapus.";
    header('Location: ' . $base . 'pesanan');
    exit;
}

$flashError = $_SESSION['flash_error'] ?? null;
$flashSucces = $_SESSION['flash_sukses'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_sukses']);

$title = 'Hapus Pesanan';
$breadcrumb = [
    ['label' => 'Pesanan', 'url' => 'pesanan'],
    ['label' => 'Hapus Pesanan', 'url' => ''],
];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="max-w-md mx-auto my-6 sm:my-10">
    <div class="bg-white rounded-3xl p-8 sm:p-10 shadow-xs border border-slate-100 text-center">
        <svg class="w-12 h-12 mx-auto text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
        <h1 class="text-2xl font-bold text-slate-800 mt-4">Hapus Pesanan</h1>
        <p class="text-sm text-slate-500 mt-2">Apakah Anda yakin ingin menghapus pesanan <span class="font-semibold text-slate-700"><?php echo e($pesanan['kode_pesanan']); ?></span>?</p>
        <p class="text-xs text-slate-400 mt-1">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="<?php echo $base; ?>pesanan" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
            <form method="post" class="inline">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <button type="submit" class="px-4 py-2 bg-rose-500 text-white rounded-xl hover:bg-rose-600 transition-colors">Hapus</button>
            </form>
        </div>
    </div>
</div>

<?php if ($flashError): ?>
    <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span><?php echo e($flashError); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<?php if ($flashSucces): ?>
    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-2xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span><?php echo e($flashSucces); ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
