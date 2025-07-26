<?php

namespace App\Test\Unit\Infrastructure\Http\Okx;

use App\Infrastructure\Service\OkxClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Client\HttpClientInterface;
use Tests\Stub\FakeRedis;

class OkxClientTest extends TestCase
{
    public function testSendBalanceRequestReturnsValidResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = new FakeRedis();
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $expectedBody = json_encode([
            'code' => '0',
            'data' => [[
                'details' => [['ccy' => 'BTC', 'bal' => '0.5']]
            ]]
        ]);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://www.okx.com/api/v5/account/balance', $this->callback(function ($options) use ($apiKey) {
                return isset($options['headers']['OK-ACCESS-KEY']) &&
                    $options['headers']['OK-ACCESS-KEY'] === $apiKey;
            }))
            ->willReturn(new GuzzleResponse(200, [], $expectedBody));

        $pdo = $this->createMock(\PDO::class);
        $client = new OkxClient($httpClient, $logger, $redis, $pdo);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertEquals([['ccy' => 'BTC', 'bal' => '0.5']], $response);
    }

    public function testThrowsExceptionWhenRateLimitExceeded(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = new FakeRedis();
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $redis = new FakeRedis();
        $pdo = $this->createMock(\PDO::class);
        $client = new OkxClient($httpClient, $logger, $redis, $pdo);

        $this->expectException(\RuntimeException::class);
        $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);
    }

    public function testAddsSimulatedTradingHeader(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = new FakeRedis();
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = true;

        $expectedBody = json_encode([
            'code' => '0',
            'data' => [[
                'details' => []
            ]]
        ]);

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://www.okx.com/api/v5/account/balance', $this->callback(function ($options) {
                return isset($options['headers']['x-simulated-trading']) &&
                    $options['headers']['x-simulated-trading'] === '1';
            }))
            ->willReturn(new GuzzleResponse(200, [], $expectedBody));

        $pdo = $this->createMock(\PDO::class);
        $client = new OkxClient($httpClient, $logger, $redis, $pdo);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertEquals([], $response);
    }
}
