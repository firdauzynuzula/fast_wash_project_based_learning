<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// TODO: csrf_check(); cari user by username; password_verify();
//       sukses -> set $_SESSION['user_id'] & $_SESSION['nama'], redirect ke ../index
//       gagal  -> redirect kembali ke login

header('Location: login');
exit;
