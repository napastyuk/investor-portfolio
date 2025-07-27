<?php

// Базовая валидация переменных окружения
$requiredEnv = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
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
        'dsn' => sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_DATABASE']
        ),
        'user' => $_ENV['DB_USERNAME'],
        'pass' => $_ENV['DB_PASSWORD'],
    ],
];
