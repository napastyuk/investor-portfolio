<?php declare(strict_types=1);

use App\Interface\Http\Middleware\LoggingMiddleware;

return function (Slim\App $app) {
    $container = $app->getContainer();

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // добавляем логирование
    $app->add($container->get(LoggingMiddleware::class)); 
};
