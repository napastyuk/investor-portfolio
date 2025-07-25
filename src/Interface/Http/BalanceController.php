<?php

namespace App\Interface\Http;

use App\Infrastructure\Http\Okx\OkxClient;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

readonly class BalanceController
{
    public function __construct(
        private Client $http,
        private \Predis\Client $redis,
        private PDO $pdo,
        private LoggerInterface $logger
    ) {}

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $client = new OkxClient(
            $this->http,
            $this->redis,
            $user['okx_api_key'],
            $user['okx_secret_key'],
            $user['okx_passphrase'],
            $this->logger
        );

        $balances = $client->getBalances();

        $stmt = $this->pdo->prepare(
            'INSERT INTO user_balances (user_id, ccy, eq, eq_usd, rate)
            VALUES (:user_id, :ccy, :eq, :eq_usd, :rate)
            ON CONFLICT (user_id, ccy) DO UPDATE
            SET eq = EXCLUDED.eq,
                eq_usd = EXCLUDED.eq_usd,
                rate = EXCLUDED.rate,
                created_at = CURRENT_TIMESTAMP'
        );

        $inserted = 0;

        foreach ($balances as $item) {
            $eq = (float)$item['eq'];
            $eqUsd = (float)$item['eqUsd'];
            $rate = $eq > 0 ? $eqUsd / $eq : null;

            $stmt->execute([
                'user_id' => $user['id'],
                'ccy' => $item['ccy'],
                'eq' => $eq,
                'eq_usd' => $eqUsd,
                'rate' => $rate,
            ]);

            $inserted++;
        }

        $this->logger->info("User #{$user['id']} inserted {$inserted}");

        $response->getBody()->write(json_encode(['inserted' => $inserted], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $stmt = $this->pdo->prepare('SELECT * FROM user_balances WHERE user_id = :id ORDER BY created_at DESC LIMIT 100');
        $stmt->execute(['id' => $user['id']]);
        $result = $stmt->fetchAll();

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
