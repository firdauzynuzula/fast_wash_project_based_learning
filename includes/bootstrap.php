<?php
// Wajib di-require PERTAMA oleh setiap entry file di webroot.
session_start();

// $base = prefix relatif untuk URL browser ('./' di root, '../' di subfolder modul).
// Dihitung dari SCRIPT_FILENAME = ENTRY file (bukan file include),
// sehingga benar di semua kedalaman dan di subfolder install.
$rootDir   = str_replace('\\', '/', dirname(__DIR__));
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$rel       = trim(substr($scriptDir, strlen($rootDir)), '/');
$base      = $rel === '' ? './' : str_repeat('../', substr_count($rel, '/') + 1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/koneksi.php';   // menghasilkan $pdo
