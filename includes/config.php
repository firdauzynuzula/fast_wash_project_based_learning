<?php
// Kredensial dipisah dari kode sumber (pola jobsheet-14).
// Di hosting/produksi isi lewat environment variable; default di bawah
// hanya untuk pengembangan lokal. Jangan commit kredensial asli.
return [
    'db_host'         => getenv('DB_HOST') ?: 'localhost',
    'db_port'         => getenv('DB_PORT') ?: '5432',
    'db_name'         => getenv('DB_NAME') ?: 'fast_wash',
    'db_user'         => getenv('DB_USER') ?: 'postgres',
    'db_pass'         => getenv('DB_PASS') ?: '',
    'db_sslmode'      => getenv('DB_SSLMODE') ?: 'prefer',
    'app_debug'       => (getenv('APP_DEBUG') ?: '') === 'true',
    'app_url'         => getenv('APP_URL') ?: 'http://localhost:8000',

    // Konfigurasi Brevo (Sendinblue) REST API
    'brevo_api_key'   => getenv('BREVO_API_KEY') ?: '',
    'mail_from_email' => getenv('MAIL_FROM_EMAIL') ?: 'support@fastwash.web.id',
    'mail_from_name'  => getenv('MAIL_FROM_NAME') ?: 'Fast Wash Official',
];
