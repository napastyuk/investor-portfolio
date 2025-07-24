<?php

namespace App\Interface\Http\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpNotFoundException;

class LoggingMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Пропускаем шумные пути без логирования
        $excluded = ['/favicon.ico'];
        if (str_starts_with($path, '/.well-known/')) {
            return $handler->handle($request);
        }

        if (!in_array($path, $excluded, true)) {
            $this->logger->info('Incoming Request', [
                'method' => $request->getMethod(),
                'uri' => (string)$request->getUri(),
            ]);
        }

        try {
            $response = $handler->handle($request);
            $this->logger->info('Outgoing response', [
                'status' => $response->getStatusCode()
            ]);
            return $response;
        } catch (HttpNotFoundException $e) {
            $this->logger->warning('404 Not Found', [
                'method' => $request->getMethod(),
                'uri' => (string)$request->getUri(),
            ]);
            throw $e; // пробрасываем дальше, чтобы сработал NotFoundHandler
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}