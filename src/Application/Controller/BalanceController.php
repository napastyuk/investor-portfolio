<?php

namespace App\Application\Controller;

use App\Infrastructure\Okx\OkxClient;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

readonly class BalanceController
{
    public function __construct(
        private OkxClient $client,
        private PDO $pdo,
        private LoggerInterface $logger
    ) {}

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $balances = $this->client->getBalances();

        $stmt = $this->pdo->prepare(
            'INSERT INTO wallet_balances (ccy, eq, eq_usd, rate)
            VALUES (:ccy, :eq, :eq_usd, :rate)
            ON CONFLICT (ccy) DO UPDATE
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
                'ccy' => $item['ccy'],
                'eq' => $eq,
                'eq_usd' => $eqUsd,
                'rate' => $rate,
            ]);

            $inserted++;
        }

        $this->logger->info("inserted {$inserted}");

        $response->getBody()->write(json_encode(['inserted' => $inserted], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stmt = $this->pdo->query('SELECT * FROM wallet_balances ORDER BY created_at DESC LIMIT 100');
        $result = $stmt->fetchAll();

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
