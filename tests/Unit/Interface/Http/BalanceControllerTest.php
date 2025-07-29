<?php declare(strict_types=1);

namespace Tests\Unit\Interface\Http;

use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;
use App\Interface\Http\BalanceController;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BalanceControllerTest extends TestCase
{
    public function testImportSuccess()
    {
        $user = ['id' => 1];
        $balances = [
            ['ccy' => 'BTC', 'eq' => '0.5', 'eqUsd' => '30000'],
            ['ccy' => 'ETH', 'eq' => '2.0', 'eqUsd' => '6000'],
        ];

        $okxClient = $this->createMock(OkxClientInterface::class);
        $okxClient->expects($this->once())
            ->method('fetchBalances')
            ->with($user['id'])
            ->willReturn($balances);

        $balanceRepo = $this->createMock(BalanceRepositoryInterface::class);
        $balanceRepo->expects($this->once())
            ->method('saveBalance')
            ->with($user['id'], $balances);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $controller = new BalanceController($okxClient, $balanceRepo, $logger);

        $request = (new ServerRequest('POST', '/balances/import'))
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

    public function testImportFailure()
    {
        $user = ['id' => 1];

        $okxClient = $this->createMock(OkxClientInterface::class);
        $okxClient->method('fetchBalances')->willReturn([['ccy' => 'BTC']]);

        $balanceRepo = $this->createMock(BalanceRepositoryInterface::class);
        $balanceRepo->method('saveBalance')->willThrowException(new \RuntimeException('DB failure'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $controller = new BalanceController($okxClient, $balanceRepo, $logger);

        $request = (new ServerRequest('POST', '/balances/import'))->withAttribute('user', $user);
        $response = $controller->import($request, new Response());

        $this->assertEquals(500, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame('error', $body['status']);
        $this->assertStringContainsString('Failed to import balances', $body['message']);
    }

    public function testListReturnsBalances()
    {
        $user = ['id' => 2];
        $balances = [
            ['ccy' => 'BTC', 'eq' => 0.1, 'eq_usd' => 3000],
        ];

        $okxClient = $this->createMock(OkxClientInterface::class);
        $balanceRepo = $this->createMock(BalanceRepositoryInterface::class);
        $balanceRepo->expects($this->once())
            ->method('getBalancesByUserId')
            ->with($user['id'])
            ->willReturn($balances);

        $logger = $this->createMock(LoggerInterface::class);
        $controller = new BalanceController($okxClient, $balanceRepo, $logger);

        $request = (new ServerRequest('GET', '/balances'))->withAttribute('user', $user);
        $response = $controller->list($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame(['balances' => $balances], $body);
    }

    public function testListWithMissingUserReturnsEmpty()
    {
        $okxClient = $this->createMock(OkxClientInterface::class);
        $balanceRepo = $this->createMock(BalanceRepositoryInterface::class);
        $balanceRepo->expects($this->once())
            ->method('getBalancesByUserId')
            ->with(0)
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $controller = new BalanceController($okxClient, $balanceRepo, $logger);

        $request = new ServerRequest('GET', '/balances');
        $response = $controller->list($request, new Response());

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        $this->assertSame(['balances' => []], $body);
    }
}