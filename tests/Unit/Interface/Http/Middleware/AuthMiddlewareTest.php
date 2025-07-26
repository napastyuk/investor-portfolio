<?php

namespace App\Test\Unit\Interface\Http\Middleware;

use App\Interface\Http\Middleware\AuthMiddleware;
use Nyholm\Psr7\ServerRequest;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Nyholm\Psr7\Response;

class AuthMiddlewareTest extends TestCase
{
    //негативные сценарии, Нет заголовка — ошибка
    public function testMissingAuthorizationHeaderThrowsException()
    {
        $pdo = $this->createMock(PDO::class);
        $middleware = new AuthMiddleware($pdo);

        $request = new ServerRequest('GET', '/balances');
        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->expectException(HttpUnauthorizedException::class);
        $middleware->process($request, $handler);
    }

    //	токен не найден — ошибка
    public function testInvalidTokenThrowsException()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $stmt->expects($this->once())->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $middleware = new AuthMiddleware($pdo);

        $request = (new ServerRequest('GET', '/balances'))
            ->withHeader('Authorization', 'Bearer invalid_token');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->expectException(HttpUnauthorizedException::class);
        $middleware->process($request, $handler);
    }

    //положительный сценарий, найден пользователь и user добавлен в атрибут запроса
    public function testValidTokenPassesAndAddsUser()
    {
        $user = ['id' => 1, 'name' => 'Илья'];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['token' => 'valid_token']);
        $stmt->expects($this->once())->method('fetch')->willReturn($user);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $middleware = new AuthMiddleware($pdo);

        $request = (new ServerRequest('GET', '/balances'))
            ->withHeader('Authorization', 'Bearer valid_token');

        $handler = $this->createMock(RequestHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($req) use ($user) {
                return $req->getAttribute('user') === $user;
            }))
            ->willReturn(new Response());

        $response = $middleware->process($request, $handler);
        $this->assertInstanceOf(Response::class, $response);
    }
}
