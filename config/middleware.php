<?php

use App\Application\Middleware\LoggingMiddleware;

return function (Slim\App $app) {
    $container = $app->getContainer();

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();

    // добавляем логирование
    $app->add($container->get(LoggingMiddleware::class)); 
};
