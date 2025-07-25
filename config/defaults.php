<?php

use Dotenv\Exception\InvalidPathException;

// Базовая валидация переменных окружения
$requiredEnv = ['DB_DSN', 'DB_USER', 'DB_PASS'];
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
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
];
