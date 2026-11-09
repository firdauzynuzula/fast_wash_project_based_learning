<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';

// Sudah login -> langsung ke dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

// Data dummy akun yang dianggap sudah terdaftar (tanpa database).
$dummyRegisteredEmails = [
    'admin@fastwash.com',
    'fastwash@gmail.com',
];

$error   = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$oldNama       = '';
$oldEmail      = '';
$oldNoWhatsapp = '';

// Proses form register demo jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $nama            = trim($_POST['nama_lengkap'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $noWhatsapp      = trim($_POST['no_whatsapp'] ?? '');
    $password        = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $setuju          = isset($_POST['setuju']);

    $oldNama       = $nama;
    $oldEmail      = $email;
    $oldNoWhatsapp = $noWhatsapp;

    if ($nama === '' || $email === '' || $noWhatsapp === '' || $password === '' || $passwordConfirm === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (in_array(strtolower($email), $dummyRegisteredEmails, true)) {
        $error = 'Email sudah terdaftar (data dummy).';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif (!$setuju) {
        $error = 'Anda harus menyetujui Ketentuan Layanan & Privasi Fast Wash.';
    } else {
        // Demo saja: simpan ke session sementara (tidak disimpan ke database).
        $_SESSION['dummy_registered_user'] = [
            'nama_lengkap' => $nama,
            'email'        => $email,
            'no_whatsapp'  => $noWhatsapp,
        ];
        $success       = 'Pendaftaran demo berhasil untuk "' . $nama . '" (' . $email . '). Data tidak disimpan ke database.';
        $oldNama       = '';
        $oldEmail      = '';
        $oldNoWhatsapp = '';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash — Buat Akun Baru</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body class="min-h-screen overflow-hidden bg-[#E2E8F0] flex items-center justify-center p-4">

    <div class="w-full max-w-4xl grid md:grid-cols-2 rounded-2xl overflow-hidden shadow-xl bg-white">

        <!-- Panel kiri -->
        <section class="bg-[#d9e8f8] p-10 flex flex-col">
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
            <h1 class="mt-8 text-4xl font-extrabold text-[#16305b]">Handled with care</h1>
            <p class="mt-4 text-[#5b6b82] leading-relaxed">Mulai dari penjemputan di depan pintu hingga pengiriman dengan pakaian terlipat rapi.</p>
            <img src="<?php echo $base; ?>assets/img/laundry-illustration.webp" alt="Ilustrasi mesin cuci, keranjang laundry, dan tumpukan handuk" class="mt-auto pt-8 w-full max-w-sm mx-auto">
        </section>

        <!-- Panel kanan: form buat akun baru -->
        <section class="p-10 flex flex-col justify-center">
            <h2 class="text-3xl font-extrabold text-[#16305b]">Buat Akun Baru</h2>

            <?php if (!empty($error)): ?>
            <p role="alert" class="mt-3 rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-700"><?php echo e($error); ?></p>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <p role="status" class="mt-3 rounded-lg bg-[#eaf1fb] px-4 py-2 text-sm font-medium text-[#1668dc]"><?php echo e($success); ?></p>
            <?php endif; ?>

            <form action="" method="post" class="mt-4 space-y-3">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="flex flex-col gap-1">
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-900">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </div>
                        <input id="nama_lengkap" name="nama_lengkap" type="text" autocomplete="name" required
                               value="<?php echo e($oldNama); ?>"
                               placeholder="Fast Wash"
                               class="block w-full ps-10 pe-3.5 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="email" class="block text-sm font-medium text-gray-900">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 0 9 6 9-6"/>
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               value="<?php echo e($oldEmail); ?>"
                               placeholder="fastwash@gmail.com"
                               class="block w-full ps-10 pe-3.5 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="no_whatsapp" class="block text-sm font-medium text-gray-900">No. WhatsApp</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.702 6.896 1.519 10.117 3.22 3.221 6.914 4.745 10.12 1.535a1.601 1.601 0 0 0 0-2.456Z"/>
                            </svg>
                        </div>
                        <input id="no_whatsapp" name="no_whatsapp" type="tel" autocomplete="tel" required
                               value="<?php echo e($oldNoWhatsapp); ?>"
                               placeholder="0812 3456 7890"
                               class="block w-full ps-10 pe-3.5 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="password" class="block text-sm font-medium text-gray-900">Kata Sandi</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required
                               placeholder="Minimal 8 karakter"
                               class="block w-full ps-3.5 pe-10 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                        <button type="button" id="toggle-password" aria-label="Tampilkan kata sandi"
                                class="absolute inset-y-0 end-0 flex items-center pe-3.5 cursor-pointer text-gray-500 hover:text-gray-900">
                            <svg id="icon-eye" class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg id="icon-eye-off" class="w-4 h-4 hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.933 13.909A4.357 4.357 0 0 1 3 12c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 21 12c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M5 19 19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="password_confirm" class="block text-sm font-medium text-gray-900">Konfirmasi Kata Sandi</label>
                    <div class="relative">
                        <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" required
                               placeholder="Ulangi kata sandi"
                               class="block w-full ps-3.5 pe-10 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                        <button type="button" id="toggle-password-confirm" aria-label="Tampilkan konfirmasi kata sandi"
                                class="absolute inset-y-0 end-0 flex items-center pe-3.5 cursor-pointer text-gray-500 hover:text-gray-900">
                            <svg id="icon-eye-confirm" class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                            <svg id="icon-eye-off-confirm" class="w-4 h-4 hidden" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.933 13.909A4.357 4.357 0 0 1 3 12c0-1 4-6 9-6m7.6 3.8A5.068 5.068 0 0 1 21 12c0 1-3 6-9 6-.314 0-.62-.014-.918-.04M5 19 19 5m-4 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center text-sm">
                    <input id="setuju" type="checkbox" name="setuju" required
                           class="w-4 h-4 border border-gray-200 rounded bg-gray-50 accent-blue-700 focus:ring-2 focus:ring-blue-100">
                    <label for="setuju" class="ms-2 text-sm text-[#6b7a90] select-none">
                        Saya menyetujui <a href="#" class="font-semibold text-[#1668dc] hover:underline">Ketentuan Layanan</a> &amp; <a href="#" class="font-semibold text-[#1668dc] hover:underline">Privasi</a> Fast Wash.
                    </label>
                </div>

                <button type="submit"
                        class="w-full cursor-pointer rounded-lg bg-[#1668dc] py-2.5 font-semibold text-white transition-colors hover:bg-[#1259c4]">
                    Daftar Sekarang
                </button>
            </form>

            <p class="mt-4 text-center text-sm text-[#6b7a90]">
                Sudah punya akun? <a href="<?php echo $base; ?>auth/login" class="font-semibold text-[#1668dc] hover:underline">Masuk di sini</a>
            </p>
        </section>
    </div>

    <script src="<?php echo $base; ?>assets/js/app.js"></script>
</body>
</html>
