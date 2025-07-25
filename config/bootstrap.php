<?php
use DI\ContainerBuilder;
use Slim\App;

require __DIR__ . '/../vendor/autoload.php';

// Подключаем .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Instantiate DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Add container definitions
$containerBuilder->addDefinitions(__DIR__ . '/container.php');

// Build DI container
$container = $containerBuilder->build();

// Create app instance
return $container->get(App::class);
