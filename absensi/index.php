<?php
/**
 * Fast Wash - Modul Absensi: Entry Point GET (/absensi, /absensi/index)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/controller.php';

absensi_tampilkan_halaman($pdo, $base);
