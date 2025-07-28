<?php declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;

//подключаем автозагрузщик Composer
require __DIR__ . '/../vendor/autoload.php';

// подключаем .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// создаем билдер DI контейнера
$containerBuilder = new ContainerBuilder();

// загружается файл с определениями зависимостей для DI
$containerBuilder->addDefinitions(__DIR__ . '/container.php');

// собираем DI контейнер
$container = $containerBuilder->build();

// создаем экземпляр slim-приложения
return $container->get(App::class);
