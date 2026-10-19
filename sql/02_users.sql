CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,   -- password_hash() / password_verify()
    role          VARCHAR(20) NOT NULL DEFAULT 'petugas'
);
