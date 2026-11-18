<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_db.php';

if (!empty($pdo)) {
    akhiri_sesi($pdo);
} else {
    session_destroy();
}

header('Location: ' . $base . '');
exit;
