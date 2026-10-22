-- Active: 1791336200846@@127.0.0.1@5432@fast_wash@public
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    nama          VARCHAR(100) NOT NULL,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,   -- password_hash() / password_verify()
    role          VARCHAR(20) NOT NULL DEFAULT 'petugas'
);
