<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';

// Sudah login -> langsung ke dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

// Pesan error satu kali tampil (diset oleh auth/proses_login).
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

// Proses form login jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/auth_default.php';
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (cek_login_default($username, $password)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = 1;
        $_SESSION['nama']    = 'Admin';
        $_SESSION['role']    = 'admin';
        header('Location: ' . $base . 'dashboard');
        exit;
    }

    $error = 'Username atau kata sandi salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fast Wash — Masuk</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>
<body class="min-h-screen bg-[#E2E8F0] flex items-center justify-center p-4">

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
            <img src="<?php echo $base; ?>assets/img/laundry-illustration.png" alt="Ilustrasi mesin cuci, keranjang laundry, dan tumpukan handuk" class="mt-auto pt-8 w-full max-w-sm mx-auto">
        </section>

        <!-- Panel kanan: form masuk -->
        <section class="p-10 flex flex-col justify-center">
            <p class="text-sm font-bold text-[#1668dc]">Selamat datang kembali!</p>
            <h2 class="mt-1 text-3xl font-extrabold text-[#16305b]">Masuk Fast Wash</h2>
            <p class="mt-2 text-sm text-[#6b7a90]">Pilih peran Anda untuk melihat ruang kerja yang tepat.</p>

            <div class="mt-6 grid grid-cols-2 rounded-full bg-[#eaf1fb] p-1 text-sm font-semibold" role="group" aria-label="Pilih peran">
                <button type="button" data-role="pemilik" aria-pressed="false"
                        class="js-role cursor-pointer py-2 text-center rounded-full text-[#16305b]">Pemilik</button>
                <button type="button" data-role="karyawan" aria-pressed="true"
                        class="js-role cursor-pointer py-2 text-center rounded-full bg-white text-[#1668dc] shadow-sm">Karyawan</button>
            </div>

            <?php if (!empty($error)): ?>
            <p role="alert" class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700"><?php echo e($error); ?></p>
            <?php endif; ?>

            <form action="" method="post" class="mt-6 space-y-5">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="role" id="role" value="karyawan">

                <div>
                    <label for="email" class="block mb-2.5 text-sm font-medium text-gray-900">Email atau nomor HP</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </div>
                        <input id="email" name="username" type="text" autocomplete="username" required
                               placeholder="fastwash@gmail.com"
                               class="block w-full ps-10 pe-3.5 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                    </div>
                </div>

                <div>
                    <label for="password" class="block mb-2.5 text-sm font-medium text-gray-900">Kata Sandi</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               placeholder="••••••••"
                               class="block w-full ps-3.5 pe-10 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
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

                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center">
                        <input id="ingat" type="checkbox" name="ingat" checked
                               class="w-4 h-4 border border-gray-200 rounded bg-gray-50 accent-blue-700 focus:ring-2 focus:ring-blue-100">
                        <label for="ingat" class="ms-2 text-sm font-medium text-gray-900 select-none">Ingat saya</label>
                    </div>
                    <a href="#" class="font-semibold text-[#1668dc] hover:underline">Lupa kata sandi?</a>
                </div>

                <button type="submit"
                        class="w-full cursor-pointer rounded-lg bg-[#1668dc] py-3 font-semibold text-white transition-colors hover:bg-[#1259c4]">
                    Masuk
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#6b7a90]">
                Pelanggan baru? <a href="#" class="font-semibold text-[#1668dc] hover:underline">Buat akun</a>
            </p>
        </section>
    </div>

    <script src="<?php echo $base; ?>assets/js/app.js"></script>
</body>
</html>
