<?php

// Общие настройки приложения
return [
    'db' => [
        'host'     => $_ENV['DB_HOST'] ?? 'localhost',
        'port'     => (int)($_ENV['DB_PORT'] ?? 5432),
        'database' => $_ENV['DB_DATABASE'] ?? 'slim',
        'username' => $_ENV['DB_USERNAME'] ?? 'slim',
        'password' => $_ENV['DB_PASSWORD'] ?? 'slim',
    ],

    'error' => [
        'log_errors' => true,
    ],
];
