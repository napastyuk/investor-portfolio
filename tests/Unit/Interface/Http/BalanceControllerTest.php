<?php declare(strict_types=1);

namespace App\Test\Unit\Interface\Http;

use App\Interface\Http\BalanceController;
use App\Interface\Http\Responder\JsonResponder;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;

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
            ->method('fetchBalances') 
            ->with($user['id'])
            ->willReturn($balances);

        $balanceRepository = $this->createMock(BalanceRepositoryInterface::class);
        $balanceRepository->expects($this->once())
            ->method('saveBalances')
            ->with($user['id'], $balances);

        $controller = new BalanceController($okxClient, $balanceRepository);

        $request = (new ServerRequest('POST', '/balances'))
            ->withAttribute('user', $user);

        $response = $controller->import($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson((string)$response->getBody());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame([
            'status' => 'success',
            'balances' => $balances,
        ], $body);
    }


    public function testMissingUserAttributeReturnsEmpty()
    {
        $okxClient = $this->createMock(OkxClientInterface::class);
        $balanceRepository = $this->createMock(BalanceRepositoryInterface::class);

        $balanceRepository->expects($this->once())
            ->method('getBalancesByUserId')
            ->with(0)
            ->willReturn([]);

        $controller = new BalanceController($okxClient, $balanceRepository);

        $request = new ServerRequest('GET', '/balances');
        $response = $controller->list($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson((string)$response->getBody());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame(['balances' => []], $body);
    }
}
