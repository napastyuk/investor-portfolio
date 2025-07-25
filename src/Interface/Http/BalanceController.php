<?php

namespace App\Interface\Http;

use App\Infrastructure\Http\Okx\OkxClient;
use App\Interface\Http\Responder\JsonResponder;
use App\Client\HttpClientInterface;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

readonly class BalanceController
{
    public function __construct(
        private HttpClientInterface $http,
        private \Predis\Client $redis,
        private PDO $pdo,
        private LoggerInterface $logger,
        private LoggerInterface $okxLogger,
        private JsonResponder $responder
    ) {}

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $simulated = (bool)($user['is_test_user'] ?? false); 

        $client = new OkxClient(
            $this->http,
            $this->redis,
            $user['okx_api_key'],
            $user['okx_secret_key'],
            $user['okx_passphrase'],
            $this->okxLogger, 
            $simulated
        );

        try {
            $balances = $client->getBalances();
        } catch (\Throwable $e) {
            return $this->responder->error($response, [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 502);
        };

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

        return $this->responder->success($response, ['inserted' => $inserted]);
    }

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        $stmt = $this->pdo->prepare('SELECT * FROM user_balances WHERE user_id = :id ORDER BY created_at DESC');
        $stmt->execute(['id' => $user['id']]);
        $result = $stmt->fetchAll();

        return $this->responder->success($response, $result);
    }
}
