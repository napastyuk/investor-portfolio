<?php

namespace App\Infrastructure\Http\Okx;

use App\Client\HttpClientInterface;
use Predis\Client as Redis;
use Psr\Log\LoggerInterface;

class OkxClient
{
    private const BASE_URI = 'https://www.okx.com';

    public function __construct(
        private HttpClientInterface $http,
        private Redis $redis,
        private string $apiKey,
        private string $secretKey,
        private string $passphrase,
        private LoggerInterface $logger,
        private bool $simulated = false // ← Новый параметр
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

        if ($this->simulated) {
            $headers['x-simulated-trading'] = '1';
        }

        $uri = self::BASE_URI . $path;
        $start = microtime(true);
        $status = null;

        try {
            $options = ['headers' => $headers];
            if (!empty($body)) {
                $options['body'] = $body;
            }

            $response = $this->http->request($method, $uri, $options);
            $duration = round((microtime(true) - $start) * 1000, 2);

            $responseBody = $response->getBody()->getContents();
            $status = $response->getStatusCode();

            $decoded = json_decode($responseBody, true);

            $this->logger->info('OKX HTTP Request', [
                'method' => $method,
                'path' => $path,
                'body' => $body,
                'status' => $status,
                'response' => $decoded,
                'duration_ms' => $duration
            ]);

            if (!isset($decoded['code']) || $decoded['code'] !== '0') {
                $msg = $decoded['msg'] ?? 'Unknown error';
                $code = $decoded['code'] ?? 'N/A';
                throw new \RuntimeException("OKX API Error (code {$code}): {$msg}");
            }

            return $decoded;
        } catch (\Throwable $e) {
            $this->logger->error('OKX Request failed', [
                'method' => $method,
                'path' => $path,
                'body' => $body,
                'status' => $status ?? null,
                'error' => $e->getMessage(),
                'duration_ms' => $duration ?? null,
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
        $max = 5;

        while (true) {
            $count = (int) $this->redis->get($key);

            if ($count < $max) {
                // лимит не достигнут — увеличиваем и ставим TTL
                $this->redis->multi();
                $this->redis->incr($key);
                $this->redis->expire($key, 1);
                $this->redis->exec();
                break;
            }

            // при превышении лимита ждём до следующей секунды 200мс
            usleep(200_000); 
            //и в цикле отправляем на выполнение снова
        }
    }

}
