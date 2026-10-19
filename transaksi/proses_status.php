<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

// TODO: csrf_check(); validasi transisi baru->proses->selesai->diambil; prepared UPDATE status + tanggal

header('Location: status');
exit;
