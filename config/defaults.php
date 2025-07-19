<?php
/**
 * Default configuration values.
 *
 * This file should contain all keys, even secret ones to serve as template.
 *
 * This is the first file loaded in settings.php and can safely define arrays
 * without the risk of overwriting something.
 * The only file where the following is permitted: $settings['db'] = ['key' => 'val', 'nextKey' => 'nextVal'];
 *
 * Documentation: https://samuel-gfeller.ch/docs/Configuration.
 */

// Init settings var
$settings = [];

// Project root dir (1 parent)
$settings['root_dir'] = dirname(__DIR__, 1);

// Enable error reporting for all errors
error_reporting(E_ALL);
// Error handling. Documentation: https://samuel-gfeller.ch/docs/Error-Handling
$settings['error'] = [
    // MUST be set to false in production to prevent disclosing sensitive information.
    // When set to true, it shows error details and throws an ErrorException for notices and warnings.
    'display_error_details' => false,
    'log_errors' => true,
];

// API documentation: https://samuel-gfeller.ch/docs/API-Endpoint
$settings['api'] = [
    // Url that is allowed to make api calls to this app
    'allowed_origin' => null,
];

$settings['db'] = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => (int)($_ENV['DB_PORT'] ?? 5432),
    'database' => $_ENV['DB_DATABASE'] ?? 'slim',
    'username' => $_ENV['DB_USERNAME'] ?? 'slim',
    'password' => $_ENV['DB_PASSWORD'] ?? 'slim',
];

return $settings;