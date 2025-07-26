<?php

namespace App\Test\Unit\Infrastructure\Http\Okx;

use App\Infrastructure\Service\OkxClient;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Predis\Client as Redis;
use App\Client\HttpClientInterface;

class OkxClientTest extends TestCase
{
    public function testSendBalanceRequestReturnsValidResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = $this->createMock(Redis::class);
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
            ->with('GET', '/api/v5/account/balance', $this->callback(function ($options) use ($apiKey) {
                return isset($options['headers']['OK-ACCESS-KEY']) &&
                       $options['headers']['OK-ACCESS-KEY'] === $apiKey;
            }))
            ->willReturn(new GuzzleResponse(200, [], $expectedBody));

        $redis->method('get')->willReturn(0);
        $redis->method('multi')->willReturn($redis);
        $redis->method('incr')->willReturn(1);
        $redis->method('expire')->willReturn(1);
        $redis->method('exec')->willReturn([1, 1]);

        $client = new OkxClient($httpClient, $logger, $redis);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertEquals([['ccy' => 'BTC', 'bal' => '0.5']], $response);
    }

    public function testThrowsExceptionWhenRateLimitExceeded(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = $this->createMock(Redis::class);
        $logger = $this->createMock(LoggerInterface::class);

        $apiKey = 'key';
        $secretKey = 'secret';
        $passphrase = 'pass';
        $isSimulated = false;

        $redis->expects($this->once())->method('get')->willReturn(5);

        $client = new OkxClient($httpClient, $logger, $redis);

        $this->expectException(\RuntimeException::class);
        $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);
    }

    public function testAddsSimulatedTradingHeader(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $redis = $this->createMock(Redis::class);
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
            ->with('GET', '/api/v5/account/balance', $this->callback(function ($options) {
                return isset($options['headers']['x-simulated-trading']) &&
                       $options['headers']['x-simulated-trading'] === '1';
            }))
            ->willReturn(new GuzzleResponse(200, [], $expectedBody));

        $redis->method('get')->willReturn(0);
        $redis->method('multi')->willReturn($redis);
        $redis->method('incr')->willReturn(1);
        $redis->method('expire')->willReturn(1);
        $redis->method('exec')->willReturn([1, 1]);

        $client = new OkxClient($httpClient, $logger, $redis);
        $response = $client->getBalances($apiKey, $secretKey, $passphrase, $isSimulated);

        $this->assertEquals([], $response);
    }
}
