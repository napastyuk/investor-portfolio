<?php
use Slim\App;
use App\Application\Controller\QuoteController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->get('/import-quotes', function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ) use ($app) {
        $controller = $app->getContainer()->get(QuoteController::class);
        return $controller->import($request, $response);
    });
};
