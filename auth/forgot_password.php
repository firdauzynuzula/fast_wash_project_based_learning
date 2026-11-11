<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/mailer.php';

// Sudah login -> langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

$error       = '';
$success     = '';
$demoLink    = '';
$targetEmail = 'to88000111@gmail.com'; // Default contoh untuk testing dummy

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Silakan masukkan alamat email yang valid.';
    } else {
        $targetEmail = $email;

        // 1. Buat token aman acak
        $plainToken = bin2hex(random_bytes(24));

        // 2. Simpan token (Sesi + Cache Server agar kebal klik dari Gmail)
        simpan_reset_token($email, $plainToken, time() + 3600);

        // 3. Susun URL Reset Kata Sandi
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $appUrl   = rtrim(getenv('APP_URL') ?: ($protocol . $host), '/');

        $resetUrl = $appUrl . '/auth/reset_password?token=' . $plainToken;

        // 4. Kirim email via Brevo REST API
        $mailResult = kirim_email_reset_password($email, 'Pengguna Fast Wash', $resetUrl);

        if ($mailResult['success'] && empty($mailResult['demo_mode'])) {
            $success = "Tautan verifikasi reset kata sandi telah berhasil dikirim ke <strong>" . e($email) . "</strong> via Brevo API. Silakan periksa Kotak Masuk atau folder Spam Anda.";
        } else {
            // Mode Demo / Fallback
            $success = "Permintaan reset kata sandi berhasil diproses untuk <strong>" . e($email) . "</strong>.";
            $demoLink = $resetUrl;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash — Lupa Kata Sandi</title>
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
                <h1 class="mt-8 text-4xl font-extrabold text-[#16305b]">Pulihkan Akses Akun</h1>
                <p class="mt-4 text-[#5b6b82] leading-relaxed">
                    Jangan khawatir, masukkan email Anda yang terdaftar dan kami akan mengirimkan tautan verifikasi untuk membuat kata sandi baru.
                </p>
            </div>
            
            <div class="pt-8">
                <div class="rounded-xl bg-white/70 backdrop-blur-xs p-4 border border-blue-100 text-xs text-[#16305b] space-y-1">
                    <div class="font-bold flex items-center gap-1.5 text-[#1668dc] mb-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Info Konfigurasi Pengujian
                    </div>
                    <div>Pengirim: <code><?php echo e(getenv('MAIL_FROM_EMAIL') ?: 'support@fastwash.web.id'); ?></code></div>
                    <div>Layanan: <span class="font-semibold text-blue-700">Brevo REST API</span></div>
                    <div>Status: <span class="font-semibold text-emerald-700">Mode Demo & Sesi Aktif</span></div>
                </div>
            </div>
        </section>

        <!-- Panel kanan: form reset request -->
        <section class="p-10 flex flex-col justify-center">
            <a href="<?php echo $base; ?>auth/login" class="inline-flex items-center gap-1 text-sm font-semibold text-[#1668dc] hover:underline mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Masuk
            </a>

            <p class="text-sm font-bold text-[#1668dc]">Verifikasi Email</p>
            <h2 class="mt-1 text-3xl font-extrabold text-[#16305b]">Lupa Kata Sandi</h2>
            <p class="mt-2 text-sm text-[#6b7a90]">Masukkan email penerima di bawah ini untuk mengirim tautan reset.</p>

            <?php if (!empty($error)): ?>
            <div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700 border border-red-200">
                <?php echo e($error); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div role="alert" class="mt-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 border border-emerald-200 space-y-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div><?php echo $success; ?></div>
                </div>

                <?php if (!empty($demoLink)): ?>
                <div class="pt-2 border-t border-emerald-200">
                    <p class="text-xs text-emerald-700 font-semibold mb-2">Pintasan Pengujian (Mode Demo):</p>
                    <a href="<?php echo e($demoLink); ?>" class="inline-block text-xs font-bold bg-emerald-600 text-white px-3.5 py-2 rounded-lg hover:bg-emerald-700 transition shadow-xs">
                        Buka Tautan Reset Sekarang &rarr;
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form action="" method="post" class="mt-6 space-y-5">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Email Penerima</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/>
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="<?php echo e($targetEmail); ?>"
                               placeholder="to88000111@gmail.com"
                               class="block w-full ps-10 pe-3.5 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-400">
                    </div>
                </div>

                <button type="submit"
                        class="w-full cursor-pointer rounded-xl bg-[#1668dc] py-3 font-semibold text-white transition-colors hover:bg-[#1259c4] shadow-sm">
                    Kirim Tautan Verifikasi
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#6b7a90]">
                Ingat kata sandi Anda? <a href="<?php echo $base; ?>auth/login" class="font-semibold text-[#1668dc] hover:underline">Masuk</a>
            </p>
        </section>
    </div>

</body>
</html>
