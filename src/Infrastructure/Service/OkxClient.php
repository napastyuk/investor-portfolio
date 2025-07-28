<?php declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Client\HttpClientInterface;
use Psr\Log\LoggerInterface;
use App\Infrastructure\Redis\SimpleRedisInterface;
use RuntimeException;
use App\Application\Service\OkxClientInterface;
use PDO;

class OkxClient implements OkxClientInterface
{
    private const BASE_URI = 'https://www.okx.com';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private SimpleRedisInterface $redis,
        private PDO $pdo
    ) {}

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
