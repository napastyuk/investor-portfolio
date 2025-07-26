<?php

namespace App\Infrastructure\Service;

use App\Client\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Predis\Client as Redis;
use RuntimeException;
use App\Application\Service\OkxClientInterface;
use Psr\Http\Message\ResponseInterface;
use PDO;

class OkxClient implements OkxClientInterface
{
    private const BASE_URI = 'https://www.okx.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private Redis $redis,
        private PDO $pdo
    ) {}

    // private function request(string $method, string $path, string $body = ''): array
    // {
    //     $this->checkRateLimit();

    //     $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
    //     $signature = $this->generateSignature($timestamp, $method, $path, $body);

    //     $headers = [
    //         'OK-ACCESS-KEY' => $this->apiKey,
    //         'OK-ACCESS-SIGN' => $signature,
    //         'OK-ACCESS-TIMESTAMP' => $timestamp,
    //         'OK-ACCESS-PASSPHRASE' => $this->passphrase,
    //         'Content-Type' => 'application/json',
    //     ];

    //     if ($this->simulated) {
    //         $headers['x-simulated-trading'] = '1';
    //     }

    //     $uri = self::BASE_URI . $path;
    //     $start = microtime(true);
    //     $status = null;

    //     try {
    //         $options = ['headers' => $headers];
    //         if (!empty($body)) {
    //             $options['body'] = $body;
    //         }

    //         $response = $this->httpClient->request($method, $uri, $options);
    //         $duration = round((microtime(true) - $start) * 1000, 2);

    //         $responseBody = $response->getBody()->getContents();
    //         $status = $response->getStatusCode();

    //         $decoded = json_decode($responseBody, true);

    //         $this->logger->info('OKX HTTP Request', [
    //             'method' => $method,
    //             'path' => $path,
    //             'body' => $body,
    //             'status' => $status,
    //             'response' => $decoded,
    //             'duration_ms' => $duration
    //         ]);

    //         if (!isset($decoded['code']) || $decoded['code'] !== '0') {
    //             $msg = $decoded['msg'] ?? 'Unknown error';
    //             $code = $decoded['code'] ?? 'N/A';
    //             throw new \RuntimeException("OKX API Error (code {$code}): {$msg}");
    //         }

    //         return $decoded;
    //     } catch (\Throwable $e) {
    //         $this->logger->error('OKX Request failed', [
    //             'method' => $method,
    //             'path' => $path,
    //             'body' => $body,
    //             'status' => $status ?? null,
    //             'error' => $e->getMessage(),
    //             'duration_ms' => $duration ?? null,
    //         ]);
    //         throw $e;
    //     }
    // }

    public function getBalances(string $apiKey, string $secretKey, string $passphrase, bool $isSimulated = false): array
    {
        $this->checkRateLimitPerUser($apiKey);

        $timestamp = $this->getTimestamp();
        $path = '/api/v5/account/balance';
        $body = '';
        $signature = $this->signRequest('GET', $path, $body, $secretKey);

        $headers = [
            'OK-ACCESS-KEY' => $apiKey,
            'OK-ACCESS-SIGN' => $signature,
            'OK-ACCESS-TIMESTAMP' => $timestamp,
            'OK-ACCESS-PASSPHRASE' => $passphrase,
            'Content-Type' => 'application/json',
        ];

        if ($isSimulated) {
            $headers['x-simulated-trading'] = '1';
        }

        $uri = self::BASE_URI . $path;

        $response = $this->httpClient->request('GET', $uri, [
            'headers' => $headers,
        ]);

        $body = json_decode((string) $response->getBody(), true);

        if (!isset($body['code']) || $body['code'] !== '0') {
            $this->logger->error('Invalid OKX API response', ['response' => $body]);
            throw new RuntimeException('Failed to fetch balances from OKX');
        }

        return $body['data'][0]['details'] ?? [];
    }


    private function getTimestamp(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    private function signRequest(string $method, string $path, string $body, string $secretKey): string
    {
        $timestamp = $this->getTimestamp();
        $message = $timestamp . $method . $path . $body;
        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

    // private function generateSignature(string $timestamp, string $method, string $path, string $body): string
    // {
    //     $prehash = $timestamp . $method . $path . $body;
    //     return base64_encode(hash_hmac('sha256', $prehash, $this->secretKey, true));
    // }

    // private function checkRateLimit(): void
    // {
    //     //TODO: добавить к ключу id пользователя чтобя лимиты были на пользователя
    //     $key = "okx_rate_limit";
    //     $max = 5;

    //     while (true) {
    //         $count = (int) $this->redis->get($key);

    //         if ($count < $max) {
    //             // лимит не достигнут — увеличиваем и ставим TTL
    //             $this->redis->multi();
    //             $this->redis->incr($key);
    //             $this->redis->expire($key, 1);
    //             $this->redis->exec();
    //             break;
    //         }

    //         // при превышении лимита ждём до следующей секунды 200мс
    //         usleep(200_000);
    //         //и в цикле отправляем на выполнение снова
    //     }
    // }

    public function fetchBalances(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT okx_api_key, okx_secret_key, okx_passphrase, is_test_user FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException("User with ID $userId not found");
        }

        return $this->getBalances(
            $user['okx_api_key'],
            $user['okx_secret_key'],
            $user['okx_passphrase'],
            (bool)$user['is_test_user']
        );
    }

    private function checkRateLimitPerUser(string $apiKey): void
    {
        $key = 'okx_rate_limit:' . md5($apiKey);
        $max = 5;

        while (true) {
            $count = (int) $this->redis->get($key);

            if ($count < $max) {
                $this->redis->multi();
                $this->redis->incr($key);
                $this->redis->expire($key, 1);
                $this->redis->exec();
                break;
            }

            // ждём 200мс до следующей попытки
            usleep(200_000);
        }
    }
}
