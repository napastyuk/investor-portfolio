<?php

use App\Domain\Service\BalanceService;

use App\Application\Service\OkxClientInterface;
use App\Infrastructure\Service\OkxClient;

use App\Infrastructure\Repository\BalanceRepository;
use App\Domain\Repository\BalanceRepositoryInterface;
use App\Interface\Http\BalanceController;
use App\Interface\Http\Middleware\AuthMiddleware;
use App\Interface\Http\Middleware\LoggingMiddleware;
use App\Interface\Http\NotFoundHandler;
use App\Interface\Http\Responder\JsonResponder;
use App\Interface\Http\AuthController;

use App\Client\HttpClientInterface;
use App\Client\GuzzleHttpClient;

use GuzzleHttp\Client as GuzzleClient;

use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;

use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ResponseFactory;

use DI\ContainerBuilder;

use Predis\Client as PredisClient;

return [
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // Загрузка маршрутов
        (require __DIR__ . '/routes.php')($app);

        // Загрузка middlewares
        (require __DIR__ . '/middleware.php')($app);

        // Обработка 404
        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setErrorHandler(
            \Slim\Exception\HttpNotFoundException::class,
            $container->get(\App\Interface\Http\NotFoundHandler::class)
        );

        return $app;
    },

    LoggerInterface::class => function () {
        $logFile = __DIR__ . '/../logs/app.log';
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0777);
        }
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
        return $logger;
    },

    'okxLogger' => function () {
        $logFile = __DIR__ . '/../logs/okx.log';
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0666);
        }

        $handler = new Monolog\Handler\RotatingFileHandler($logFile, 7, Logger::DEBUG);
        $formatter = new Monolog\Formatter\JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, true);
        $handler->setFormatter($formatter);

        $logger = new Logger('okx');
        $logger->pushHandler($handler);

        return $logger;
    },

    OkxClientInterface::class => \DI\autowire(OkxClient::class),

    ResponseFactoryInterface::class => fn() => new Psr17Factory(),
    ServerRequestFactoryInterface::class => fn() => new Psr17Factory(),

    HttpClientInterface::class => fn() => new GuzzleHttpClient(new \GuzzleHttp\Client()),

    PDO::class => function () {
        $settings = require __DIR__ . '/defaults.php';
        return new PDO(
            $settings['db']['dsn'],
            $settings['db']['user'],
            $settings['db']['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    },

    JsonResponder::class => fn() => new JsonResponder(),

    LoggingMiddleware::class => fn(ContainerInterface $c) => new LoggingMiddleware(
        $c->get(LoggerInterface::class)
    ),

    NotFoundHandler::class => fn(ContainerInterface $c) => new NotFoundHandler(
        $c->get(LoggerInterface::class)
    ),

    PredisClient::class => function () {
        return new PredisClient([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
        ]);
    },

    AuthMiddleware::class => fn(ContainerInterface $c) => new AuthMiddleware(
        $c->get(PDO::class)
    ),

    AuthController::class => fn(ContainerInterface $c) => new AuthController(
        $c->get(PDO::class),
        $c->get(JsonResponder::class)
    ),

    BalanceRepositoryInterface::class => \DI\autowire(BalanceRepository::class),

    BalanceController::class => \DI\autowire(),

];
