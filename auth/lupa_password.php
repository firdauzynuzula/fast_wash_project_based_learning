<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Jika sudah login -> langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base . 'dashboard');
    exit;
}

$error   = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$oldIdentifier = '';

// Proses reset kata sandi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $identifier      = trim($_POST['identifier'] ?? '');
    $passwordBaru    = (string) ($_POST['password_baru'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    $oldIdentifier = $identifier;

    if ($identifier === '' || $passwordBaru === '' || $passwordConfirm === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($passwordBaru) < 8) {
        $error = 'Kata sandi baru minimal 8 karakter.';
    } elseif ($passwordBaru !== $passwordConfirm) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        if (isset($pdo)) {
            try {
                // Cari pengguna berdasarkan email atau nomor telepon
                $stmt = $pdo->prepare('
                    SELECT id_pengguna, email, nama 
                    FROM v_login_identitas 
                    WHERE email = ? OR no_telepon = ? 
                    LIMIT 1
                ');
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Akun dengan email atau nomor WhatsApp tersebut tidak ditemukan.';
                } else {
                    // Update kata sandi baru
                    $passwordHash = password_hash($passwordBaru, PASSWORD_BCRYPT);
                    $stmtUpdate = $pdo->prepare('UPDATE pengguna SET password_hash = ? WHERE id_pengguna = ?');
                    $stmtUpdate->execute([$passwordHash, $user['id_pengguna']]);

                    // Catat ke riwayat reset_password jika tabel tersedia
                    try {
                        $stmtResetLog = $pdo->prepare('
                            INSERT INTO reset_password (id_pengguna, token_hash, waktu_kadaluarsa, waktu_dipakai) 
                            VALUES (?, ?, NOW() + INTERVAL \'1 hour\', NOW())
                        ');
                        $stmtResetLog->execute([$user['id_pengguna'], 'direct_reset_' . time()]);
                    } catch (Exception $eLog) {
                        // Tidak wajib
                    }

                    $_SESSION['flash_success'] = 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru.';
                    header('Location: ' . $base . 'auth/login');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        } else {
            $error = 'Koneksi database tidak tersedia.';
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
                <h1 class="mt-8 text-4xl font-extrabold text-[#16305b]">Pulihkan Akses</h1>
                <p class="mt-4 text-[#5b6b82] leading-relaxed">Atur ulang kata sandi Anda dengan memasukkan email atau nomor WhatsApp yang telah terdaftar.</p>
            </div>
            <img src="<?php echo $base; ?>assets/img/laundry-illustration.webp" alt="Ilustrasi mesin cuci" class="pt-8 w-full max-w-xs mx-auto">
        </section>

        <!-- Panel kanan: form reset password -->
        <section class="p-10 flex flex-col justify-center">
            <h2 class="text-3xl font-extrabold text-[#16305b]">Lupa Kata Sandi?</h2>
            <p class="mt-1 text-sm text-[#6b7a90]">Masukkan email/nomor HP Anda dan tentukan kata sandi baru.</p>

            <?php if (!empty($error)): ?>
            <p role="alert" class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm font-medium text-red-700"><?php echo e($error); ?></p>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <p role="status" class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm font-medium text-green-700"><?php echo e($success); ?></p>
            <?php endif; ?>

            <form action="" method="post" class="mt-6 space-y-4">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div>
                    <label for="identifier" class="block mb-2 text-sm font-medium text-gray-900">Email atau Nomor WhatsApp Terdaftar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 inset-s-0 flex items-center ps-3.5 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-4a3 3 0 0 0-3 3Zm8-9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            </svg>
                        </div>
                        <input id="identifier" name="identifier" type="text" required
                               value="<?php echo e($oldIdentifier); ?>"
                               placeholder="fastwash@gmail.com atau 081234567800"
                               class="block w-full ps-10 pe-3.5 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
                    </div>
                </div>

                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Kata Sandi Baru</label>
                    <div class="relative">
                        <input id="password" name="password_baru" type="password" minlength="8" required
                               placeholder="Minimal 8 karakter"
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

                <div>
                    <label for="password_confirm" class="block mb-2 text-sm font-medium text-gray-900">Konfirmasi Kata Sandi Baru</label>
                    <div class="relative">
                        <input id="password_confirm" name="password_confirm" type="password" minlength="8" required
                               placeholder="Ulangi kata sandi baru"
                               class="block w-full ps-3.5 pe-10 py-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-blue-700 focus:border-blue-700 shadow-xs placeholder:text-gray-500">
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

                <button type="submit"
                        class="w-full cursor-pointer rounded-lg bg-[#1668dc] py-3 font-semibold text-white transition-colors hover:bg-[#1259c4]">
                    Simpan Kata Sandi Baru
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#6b7a90]">
                Ingat kata sandi Anda? <a href="<?php echo $base; ?>auth/login" class="font-semibold text-[#1668dc] hover:underline">Masuk di sini</a>
            </p>
        </section>
    </div>

    <script src="<?php echo $base; ?>assets/js/app.js"></script>
</body>
</html>
