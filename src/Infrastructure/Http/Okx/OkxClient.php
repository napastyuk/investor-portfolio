<?php

namespace App\Infrastructure\Http\Okx;

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
        private string $passphrase,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    private function request(string $method, string $path, string $body = ''): array
    {
        $this->checkRateLimit();

        $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
        $signature = $this->generateSignature($timestamp, $method, $path, $body);

        $headers = [
            'OK-ACCESS-KEY' => $this->apiKey,
            'OK-ACCESS-SIGN' => $signature,
            'OK-ACCESS-TIMESTAMP' => $timestamp,
            'OK-ACCESS-PASSPHRASE' => $this->passphrase,
            'Content-Type' => 'application/json',
        ];

        $uri = self::BASE_URI . $path;

        $start = microtime(true);

        try {
            $options = ['headers' => $headers];
            if (!empty($body)) {
                $options['body'] = $body;
            }

            $response = $this->http->request($method, $uri, $options);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $responseBody = $response->getBody()->getContents();
            $status = $response->getStatusCode();

            $this->logger->info('OKX HTTP Request', [
                'method' => $method,
                'path' => $path,
                'body' => $body,
                'status' => $status,
                'response' => json_decode($responseBody, true),
                'duration_ms' => $duration
            ]);

            $decoded = json_decode($responseBody, true);

            if (!isset($decoded['code']) || $decoded['code'] !== '0') {
                throw new \RuntimeException("OKX API Error: {$decoded['msg']}");
            }

            return $decoded;
        } catch (\Throwable $e) {
            $this->logger->error('OKX Request failed', [
                'method' => $method,
                'path' => $path,
                'body' => $body,
                'status' => $status,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            throw $e;
        }
    }

    public function getBalances(): array
    {
        $response = $this->request('GET', '/api/v5/account/balance');
        return $response['data'][0]['details'] ?? [];
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
