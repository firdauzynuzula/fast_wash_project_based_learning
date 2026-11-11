<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';

// Sudah login -> langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

$token        = trim($_GET['token'] ?? $_POST['token'] ?? '');
$isValidToken = false;
$emailTarget  = '';
$errorMsg     = '';

// Validasi Token (Mendukung Sesi + Cache Server jika cookie belum terbawa dari Gmail)
$tokenData = ambil_reset_token($token);
if ($tokenData !== null) {
    $isValidToken = true;
    $emailTarget  = $tokenData['email'] ?? '';
} else {
    $errorMsg = empty($token)
        ? 'Token verifikasi tidak ditemukan dalam tautan.'
        : 'Tautan reset kata sandi tidak valid atau telah kedaluwarsa. Silakan ajukan permintaan baru.';
}

$error   = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash — Buat Kata Sandi Baru</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body class="min-h-screen bg-[#E2E8F0] flex items-center justify-center p-4">

    <div class="w-full max-w-4xl grid md:grid-cols-2 rounded-2xl overflow-hidden shadow-xl bg-white">

        <!-- Panel kiri -->
        <section class="bg-[#d9e8f8] p-10 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-[#1668dc] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                            <circle cx="12" cy="13" r="4"/>
                            <circle cx="8" cy="6.5" r="0.5" fill="currentColor"/>
                            <circle cx="11" cy="6.5" r="0.5" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="text-2xl font-bold text-[#1668dc]">Fast Wash</span>
                </div>
                <h1 class="mt-8 text-4xl font-extrabold text-[#16305b]">Kata Sandi Baru</h1>
                <p class="mt-4 text-[#5b6b82] leading-relaxed">
                    Buat kata sandi baru yang kuat dan mudah Anda ingat untuk melindungi akun Anda.
                </p>
            </div>

            <div class="pt-8">
                <div class="rounded-xl bg-white/70 backdrop-blur-xs p-4 border border-blue-100 text-xs text-[#16305b] space-y-1">
                    <div class="font-bold text-[#1668dc] mb-1">Tips Keamanan:</div>
                    <ul class="list-disc list-inside space-y-0.5 text-gray-600">
                        <li>Minimal 8 karakter</li>
                        <li>Gunakan kombinasi huruf dan angka</li>
                        <li>Jangan gunakan informasi pribadi</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Panel kanan: form reset password -->
        <section class="p-10 flex flex-col justify-center">

            <?php if (!$isValidToken): ?>
                <!-- Tampilan jika token tidak valid atau expired -->
                <div class="text-center py-6">
                    <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-[#16305b]">Tautan Tidak Valid</h2>
                    <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                        <?php echo e($errorMsg); ?>
                    </p>
                    <div class="mt-6 flex flex-col gap-2.5">
                        <a href="<?php echo $base; ?>auth/forgot_password"
                           class="w-full py-2.5 px-4 rounded-xl bg-[#1668dc] text-white font-semibold text-sm hover:bg-[#1259c4] transition text-center shadow-xs">
                            Kirim Ulang Tautan
                        </a>
                        <a href="<?php echo $base; ?>auth/login"
                           class="w-full py-2.5 px-4 rounded-xl border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-50 transition text-center">
                            Kembali ke Masuk
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Tampilan form jika token valid -->
                <p class="text-sm font-bold text-[#1668dc]">Langkah Terakhir</p>
                <h2 class="mt-1 text-3xl font-extrabold text-[#16305b]">Atur Kata Sandi</h2>
                <p class="mt-2 text-sm text-[#6b7a90]">
                    Akun: <strong class="text-gray-800"><?php echo e($emailTarget); ?></strong>
                </p>

                <?php if (!empty($error)): ?>
                <div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700 border border-red-200">
                    <?php echo e($error); ?>
                </div>
                <?php endif; ?>

                <form action="<?php echo $base; ?>auth/proses_reset_password" method="post" class="mt-6 space-y-5">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Kata Sandi Baru</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="8"
                                   placeholder="Minimal 8 karakter"
                                   class="block w-full ps-3.5 pe-10 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-400">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirm" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <input id="password_confirm" name="password_confirm" type="password" required autocomplete="new-password" minlength="8"
                                   placeholder="Ulangi kata sandi baru"
                                   class="block w-full ps-3.5 pe-10 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-400">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full cursor-pointer rounded-xl bg-[#1668dc] py-3 font-semibold text-white transition-colors hover:bg-[#1259c4] shadow-sm">
                        Simpan Kata Sandi Baru
                    </button>
                </form>

            <?php endif; ?>

        </section>
    </div>

</body>
</html>
