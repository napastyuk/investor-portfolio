<?php

// интерфейс и реализация для клиента OKX
use App\Application\Service\OkxClientInterface;
use App\Infrastructure\Service\OkxClient;

// интерфейс и адаптер к Redis
use App\Infrastructure\Redis\SimpleRedisInterface;
use App\Infrastructure\Redis\PredisAdapter;

// реализация и интерфейс к репозиторию с БД
use App\Infrastructure\Repository\BalanceRepository;
use App\Domain\Repository\BalanceRepositoryInterface;

// интерфейсный слой: контроллеры и middleware
use App\Interface\Http\BalanceController;
use App\Interface\Http\Middleware\AuthMiddleware;
use App\Interface\Http\Middleware\LoggingMiddleware;
use App\Interface\Http\NotFoundHandler;
use App\Interface\Http\Responder\JsonResponder;
use App\Interface\Http\AuthController;

// абстракция и реализация HTTP-клиента
use App\Client\HttpClientInterface;
use App\Client\GuzzleHttpClient;

// зависимости логгера Monolog
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

// фабрика PSR-17
use Nyholm\Psr7\Factory\Psr17Factory;

// PSR-совместимые интерфейсы
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Log\LoggerInterface;

// основной класс и экземпляр slim приложения
use Slim\App;
use Slim\Factory\AppFactory;

// Redis клиент
use Predis\Client as PredisClient;

return [
    // основной экземпляр Slim\App, инициализация HTTP-приложения
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);

        // загрузка маршрутов
        (require __DIR__ . '/routes.php')($app);

        // загрузка middlewares
        (require __DIR__ . '/middleware.php')($app);

        // обработчик для 404
        $errorMiddleware = $app->addErrorMiddleware(true, true, true); //для продакшна выставить первый параметр в false - не выводить ошибки в UI
        $errorMiddleware->setErrorHandler(
            \Slim\Exception\HttpNotFoundException::class,
            $container->get(\App\Interface\Http\NotFoundHandler::class)
        );

        return $app;
    },

    // общий логгер приложения (Monolog)
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

    // отдельный логгер для запросов OKX, чтобы не раздувать большими ответами основной лог 
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

    // PSR-17 фабрики для создания Response и ServerRequest объектов, требование slim
    ResponseFactoryInterface::class => fn() => new Psr17Factory(),
    ServerRequestFactoryInterface::class => fn() => new Psr17Factory(),

    // регистрация HTTP-клиента для внешних запросов (к OKX API)
    HttpClientInterface::class => fn() => new GuzzleHttpClient(new \GuzzleHttp\Client()),

    // PDO для PostgreSQL 
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

    // сервис-обёртка для отправки JSON-ответов в контроллерах
    JsonResponder::class => fn() => new JsonResponder(),

    // middleware логирующее HTTP-запросы
    LoggingMiddleware::class => fn(ContainerInterface $c) => new LoggingMiddleware(
        $c->get(LoggerInterface::class)
    ),

    // обработчик 404 ошибок
    NotFoundHandler::class => fn(ContainerInterface $c) => new NotFoundHandler(
        $c->get(LoggerInterface::class)
    ),

    // подключение к Redis через Predis, используется для лимитов при запросах к api okx
    PredisClient::class => function () {
        return new PredisClient([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
        ]);
    },

    // контроллер для авторизации, работа с токенами авторизации
    AuthMiddleware::class => fn(ContainerInterface $c) => new AuthMiddleware(
        $c->get(PDO::class)
    ),

    // абстрагирует работу с таблицей балансов.
    AuthController::class => fn(ContainerInterface $c) => new AuthController(
        $c->get(PDO::class),
        $c->get(JsonResponder::class)
    ),
    
    //для контроллера баллансов
    BalanceRepositoryInterface::class => \DI\autowire(BalanceRepository::class),
    BalanceController::class => \DI\autowire(),

    //связь абстракции Redis c реализацией, нужна для тестирования
    SimpleRedisInterface::class => \DI\autowire(PredisAdapter::class),

];
