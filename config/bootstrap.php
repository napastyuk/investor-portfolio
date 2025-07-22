<?php
/**
 * Start up the Slim App.
 *
 * Documentation: https://samuel-gfeller.ch/docs/Web-Server-config-and-Slim-Bootstrapping#slim-bootstrapping
 */

use DI\ContainerBuilder;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

// Подключаем .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad() не упадёт, если файла нет

// Instantiate DI ContainerBuilder
$containerBuilder = new ContainerBuilder();
// Add container definitions and build DI container
$container = $containerBuilder->addDefinitions(__DIR__ . '/container.php')->build();

$logFile = __DIR__ . '/../logs/app.log';
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0777); // или chown, если нужно
}

// Create app instance
return $container->get(App::class);
