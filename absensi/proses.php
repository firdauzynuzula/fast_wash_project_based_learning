<?php
/**
 * Fast Wash - Modul Absensi: Entry Point POST (/absensi/proses)
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/controller.php';

absensi_proses_aksi($pdo, $base);
