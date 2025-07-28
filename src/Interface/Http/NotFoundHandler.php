<?php declare(strict_types=1);

namespace App\Interface\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Nyholm\Psr7\Response;
use Throwable;

readonly class NotFoundHandler implements ErrorHandlerInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): ResponseInterface
    {
        $this->logger->warning("Route not found", [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri()
        ]);

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Not Found',
            'message' => 'The requested resource was not found.'
        ]));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
}
