<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Interface\Http\BalanceController;
use App\Interface\Http\AuthController;
use App\Interface\Http\Middleware\AuthMiddleware;

return function (App $app) {
    // Регистрация — без авторизации
    $app->post('/register', [AuthController::class, 'register']);

    // Приватные маршруты — с авторизацией
    $app->group('', function (RouteCollectorProxy $group) {
        $group->post('/balances/import', [BalanceController::class, 'import']);
        $group->get('/balances', [BalanceController::class, 'list']);
    })->add(AuthMiddleware::class);
};
