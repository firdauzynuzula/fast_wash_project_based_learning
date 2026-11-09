<?php
$tanpaDb = true;
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

session_destroy();

header('Location: ' . $base . '');
exit;
