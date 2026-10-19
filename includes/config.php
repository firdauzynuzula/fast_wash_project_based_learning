<?php
// Kredensial dipisah dari kode sumber (pola jobsheet-14).
// Di hosting/produksi isi lewat environment variable; default di bawah
// hanya untuk pengembangan lokal. Jangan commit kredensial asli.
return [
    'db_host'    => getenv('DB_HOST') ?: 'localhost',
    'db_port'    => getenv('DB_PORT') ?: '5432',
    'db_name'    => getenv('DB_NAME') ?: 'fast_wash',
    'db_user'    => getenv('DB_USER') ?: 'postgres',
    'db_pass'    => getenv('DB_PASS') ?: '',
    'db_sslmode' => getenv('DB_SSLMODE') ?: 'prefer',
    'app_debug'  => (getenv('APP_DEBUG') ?: '') === 'true',
];
