<?php declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Interface\Http\BalanceController;
use App\Interface\Http\AuthController;
use App\Interface\Http\Middleware\AuthMiddleware;
use App\Interface\Http\SwaggerController;

return function (App $app) {
    // // swagger api
    $app->get('/swagger.json', SwaggerController::class);

    // регистрация — без авторизации
    $app->post('/register', [AuthController::class, 'register']);

    // приватные маршруты — с авторизацией
    $app->group('', function (RouteCollectorProxy $group) {
        $group->post('/balances/import', [BalanceController::class, 'import']);
        $group->get('/balances', [BalanceController::class, 'list']);
    })->add(AuthMiddleware::class);
};
