<?php
use Slim\App;
use App\Application\Controller\BalanceController;

return function (App $app) {
    $app->get('/import-balances', [BalanceController::class, 'import']);
    $app->get('/balances', [BalanceController::class, 'list']);
};
