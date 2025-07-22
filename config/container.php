<?php
/**
 * Dependency Injection container configuration.
 *
 * Documentation: https://samuel-gfeller.ch/docs/Dependency-Injection.
 */

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use App\Infrastructure\Okx\OkxClient;
use Predis\Client as RedisClient;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use App\Application\Responder\JsonResponder;
use App\Application\Handler\NotFoundHandler;
use App\Application\Middleware\LoggingMiddleware;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    // Create app instance
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);
        // Register routes
        (require __DIR__ . '/routes.php')($app);

        // Register middlewares
        (require __DIR__ . '/middleware.php')($app);

        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();

        // Устанавливаем NotFoundHandler
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setErrorHandler(
            \Slim\Exception\HttpNotFoundException::class,
            $container->get(NotFoundHandler::class)
        );

        return $app;
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    PDO::class => function (ContainerInterface $c) {
        $settings = $c->get('settings')['db'];

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $settings['host'],
            $settings['port'],
            $settings['database'],
        );

        return new PDO($dsn, $settings['username'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    },

    \GuzzleHttp\Client::class => fn() => new \GuzzleHttp\Client(),

    RedisClient::class => fn () => new RedisClient(['scheme' => 'tcp', 'host' => 'redis', 'port' => 6379]),

    OkxClient::class => function (ContainerInterface $c) {
        return new OkxClient(
            new \GuzzleHttp\Client(),
            $c->get(RedisClient::class),
            $_ENV['OKX_API_KEY'],
            $_ENV['OKX_SECRET_KEY'],
            $_ENV['OKX_PASSPHRASE']
        );
    },

    LoggerInterface::class => function () {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../var/log/app.log'));
        return $logger;
    },

    JsonResponder::class => fn() => new JsonResponder(),

    LoggingMiddleware::class => fn(ContainerInterface $c) => new LoggingMiddleware(
        $c->get(LoggerInterface::class)
    ),

    NotFoundHandler::class => fn(ContainerInterface $c) => new NotFoundHandler(
        $c->get(LoggerInterface::class)
    )
];
