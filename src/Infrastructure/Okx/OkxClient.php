<?php

namespace App\Infrastructure\Okx;

use GuzzleHttp\Client;
use Predis\Client as Redis;
use DateTime;
use Exception;

class OkxClient
{
    private const BASE_URI = 'https://www.okx.com';

    public function __construct(
        private Client $http,
        private Redis $redis,
        private string $apiKey,
        private string $secretKey,
        private string $passphrase
    ) {}

    public function getBalances(): array
    {
        $this->checkRateLimit();

        $timestamp = (new DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
        $method = 'GET';
        $path = '/api/v5/account/balance';

        $signature = $this->generateSignature($timestamp, $method, $path, '');

        $headers = [
            'OK-ACCESS-KEY' => $this->apiKey,
            'OK-ACCESS-SIGN' => $signature,
            'OK-ACCESS-TIMESTAMP' => $timestamp,
            'OK-ACCESS-PASSPHRASE' => $this->passphrase,
            'Content-Type' => 'application/json',
        ];

        $response = $this->http->request($method, self::BASE_URI . $path, [
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        if (!isset($data['code']) || $data['code'] !== '0') {
            throw new Exception("OKX API Error: {$data['msg']}");
        }

        return $data['data'][0]['details'] ?? [];
    }

    private function generateSignature(string $timestamp, string $method, string $path, string $body): string
    {
        $prehash = $timestamp . $method . $path . $body;
        return base64_encode(hash_hmac('sha256', $prehash, $this->secretKey, true));
    }

    private function checkRateLimit(): void
    {
        $key = 'okx_rate_limit';
        $count = (int) $this->redis->get($key);

        if ($count >= 5) {
            throw new \RuntimeException('Rate limit exceeded. Try later.');
        }

        $this->redis->multi();
        $this->redis->incr($key);
        $this->redis->expire($key, 1);
        $this->redis->exec();
    }
}
