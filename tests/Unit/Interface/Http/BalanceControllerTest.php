<?php

namespace App\Test\Unit\Interface\Http;

use App\Interface\Http\BalanceController;
use App\Interface\Http\Responder\JsonResponder;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use App\Application\Service\OkxClientInterface;

class BalanceControllerTest extends TestCase
{
    public function testGetBalanceSuccess()
    {
        $user = ['id' => 1, 'api_key' => '...', 'simulated' => false];

        $balances = [
            ['ccy' => 'BTC', 'bal' => '0.5'],
            ['ccy' => 'ETH', 'bal' => '2.0'],
        ];

        $okxClient = $this->createMock(OkxClientInterface::class);
        $okxClient->expects($this->once())
            ->method('getBalances')
            ->with($user)
            ->willReturn($balances);

        $pdo = $this->createMock(PDO::class);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->exactly(2))->method('execute');

        $pdo->expects($this->exactly(2))->method('prepare')->willReturn($stmt);

        $controller = new BalanceController($okxClient, $pdo, new JsonResponder());

        $request = (new ServerRequest('GET', '/balances'))
            ->withAttribute('user', $user);

        $response = $controller->get($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson((string)$response->getBody());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame($balances, $body);
    }

    public function testMissingUserAttributeThrowsException()
    {
        $okxClient = $this->createMock(OkxClientInterface::class);
        $pdo = $this->createMock(PDO::class);
        $controller = new BalanceController($okxClient, $pdo, new JsonResponder());

        $request = new ServerRequest('GET', '/balances');

        $response = $controller->get($request, new Response());

        $this->assertEquals(401, $response->getStatusCode());
    }
}
