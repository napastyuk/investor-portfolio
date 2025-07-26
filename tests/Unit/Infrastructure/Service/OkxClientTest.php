<?php

namespace Tests\Unit\Infrastructure\Service;

use App\Client\HttpClientInterface;
use App\Infrastructure\Service\OkxClient;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Predis\Client as Redis;

class OkxClientTest extends TestCase
{
    public function testSendBalanceRequestReturnsValidResponse(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(json_encode(['data' => ['balance' => '100']]));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        $redis = $this->createMock(Redis::class);
        $redis->method('get')->willReturn(null);
        $redis->method('setex')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $pdo = $this->createMock(PDO::class);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = $this->createMock(Redis::class);
        $logger = $this->createMock(LoggerInterface::class);
        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $client = new OkxClient($httpClient, $logger, $redis);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testAddsSimulatedTradingHeader(): void
    {
        $expectedHeader = 'x-simulated-trading';

        $mockResponse = $this->createMock(ResponseInterface::class);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/api/v5/account/balance'),
                $this->callback(function ($options) use ($expectedHeader) {
                    return isset($options['headers'][$expectedHeader]) && $options['headers'][$expectedHeader] === '1';
                })
            )
            ->willReturn($mockResponse);

        $redis = $this->createMock(Redis::class);
        $redis->method('get')->willReturn(null);
        $redis->method('setex')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $pdo = $this->createMock(PDO::class);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = $this->createMock(Redis::class);
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $client = new OkxClient($httpClient, $logger, $redis);
        $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);
    }
}
