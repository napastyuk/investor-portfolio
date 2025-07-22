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

        $stmt = $this->pdo->prepare('INSERT INTO wallet_balances (ccy, eq, eq_usd) VALUES (:ccy, :eq, :eq_usd)');

        foreach ($balances as $item) {
            $stmt->execute([
                ':ccy' => $item['ccy'],
                ':eq' => $item['eq'],
                ':eq_usd' => $item['eqUsd'],
            ]);
        }

        $this->logger->info("inserted ".count($balances));
        $response->getBody()->write(json_encode(['inserted' => count($balances)], JSON_UNESCAPED_UNICODE));
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