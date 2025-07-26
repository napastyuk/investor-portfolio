<?php

namespace App\Test\Unit\Interface\Http;

use App\Interface\Http\AuthController;
use App\Interface\Http\Responder\JsonResponder;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class AuthControllerTest extends TestCase
{
    //положительный сценарий, все поля переданы -> пользователь создаётся -> токен возвращается
    public function testRegisterSuccess()
    {
        $pdo = $this->createMock(PDO::class);
        $responder = new JsonResponder();

        $data = [
            'name' => 'Илья',
            'okx_api_key' => 'abc',
            'okx_secret_key' => 'xyz',
            'okx_passphrase' => '123'
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $controller = new AuthController($pdo, $responder);

        $request = (new ServerRequest('POST', '/register'))
            ->withParsedBody($data);

        $response = $controller->register($request, new Response());

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJson((string)$response->getBody());
        $this->assertStringContainsString('token', (string)$response->getBody());
    }

    //негативный сценарий, нет нужных полей -> 400 Bad Request
    public function testRegisterMissingField()
    {
        $pdo = $this->createMock(PDO::class);
        $responder = new JsonResponder();
        $controller = new AuthController($pdo, $responder);

        $request = (new ServerRequest('POST', '/register'))
            ->withParsedBody(['name' => 'Илья']); // остальные поля отсутствуют

        $response = $controller->register($request, new Response());

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Missing field: okx_api_key', (string)$response->getBody());
    }
}
