<?php

use Dotenv\Exception\InvalidPathException;

// Базовая валидация переменных окружения
$requiredEnv = ['DB_DSN', 'DB_USER', 'DB_PASS', 'OKX_API_KEY', 'OKX_SECRET_KEY', 'OKX_PASSPHRASE'];
$missing = array_filter($requiredEnv, fn($key) => empty($_ENV[$key]));

if (!empty($missing)) {
    throw new RuntimeException('Missing required env vars: ' . implode(', ', $missing));
}

return [
    'root_dir' => dirname(__DIR__, 1),
    'error' => [
        'display_error_details' => true,
        'log_errors' => true,
    ],
    'okx' => [
        'api_key' => $_ENV['OKX_API_KEY'],
        'secret_key' => $_ENV['OKX_SECRET_KEY'],
        'passphrase' => $_ENV['OKX_PASSPHRASE'],
    ],
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
];
