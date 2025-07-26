<?php

namespace Tests\Unit\Infrastructure\Service;

use App\Client\HttpClientInterface;
use App\Infrastructure\Service\OkxClient;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Tests\Stub\FakeRedis;

class OkxClientTest extends TestCase
{
    public function testSendBalanceRequestReturnsValidResponse(): void
    {
        $stream = \Nyholm\Psr7\Stream::create(json_encode([
            'code' => '0',
            'data' => [
                [
                    'details' => ['balance' => '100']
                ]
            ]
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($stream);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('https://www.okx.com/api/v5/account/balance'),
                $this->anything()
            )
            ->willReturn($mockResponse);

        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $redis = new FakeRedis();
        $pdo = $this->createMock(\PDO::class);
        $client = new OkxClient($httpClient, $logger, $redis, $pdo);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('balance', $response);
    }

    public function testAddsSimulatedTradingHeader(): void
    {
        $expectedHeader = 'x-simulated-trading';

        $stream = \Nyholm\Psr7\Stream::create(json_encode([
            'code' => '0',
            'data' => [
                [
                    'details' => []
                ]
            ]
        ]));

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($stream);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('https://www.okx.com/api/v5/account/balance'),
                $this->callback(function ($options) use ($expectedHeader) {
                    return isset($options['headers'][$expectedHeader]) && $options['headers'][$expectedHeader] === '1';
                })
            )
            ->willReturn($mockResponse);

        $redis = new FakeRedis();
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = true;

        $pdo = $this->createMock(\PDO::class);
        $client = new OkxClient($httpClient, $logger, $redis, $pdo);
        $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);
    }
}
