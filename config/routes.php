<?php
use Slim\App;
use App\Interface\Http\BalanceController;

return function (App $app) {
    $app->get('/import-balances', [BalanceController::class, 'import']);
    $app->get('/balances', [BalanceController::class, 'list']);
};
