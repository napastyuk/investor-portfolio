<?php

namespace App\Interface\Http\Middleware;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpUnauthorizedException;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private PDO $pdo) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new HttpUnauthorizedException($request, 'Missing bearer token');
        }

        $token = substr($authHeader, 7);
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new HttpUnauthorizedException($request, 'Invalid token');
        }

        // Добавляем пользователя в request attribute
        return $handler->handle($request->withAttribute('user', $user));
    }
}
