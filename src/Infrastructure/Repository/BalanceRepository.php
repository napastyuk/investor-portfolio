<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\BalanceRepositoryInterface;
use DateTimeImmutable;
use PDO;
use PDOException;
use RuntimeException;
use Psr\Log\LoggerInterface;

class BalanceRepository implements BalanceRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
    ) {}

    public function saveBalance(int $userId, array $balances, ?DateTimeImmutable $createdAt = null): void
    {
        $useNow = $createdAt === null;
        $sql = 'INSERT INTO user_balances (user_id, ccy, eq, eq_usd, rate, created_at)
            VALUES (:user_id, :ccy, :eq, :eq_usd, :rate, ' . ($useNow ? 'NOW()' : ':created_at') . ')
            ON CONFLICT (user_id, ccy) DO UPDATE SET
                eq = EXCLUDED.eq,
                eq_usd = EXCLUDED.eq_usd,
                rate = EXCLUDED.rate,
                created_at = EXCLUDED.created_at';

        try {
            $stmt = $this->pdo->prepare($sql);

            foreach ($balances as $item) {
                $eq = (float)($item['eq'] ?? 0);
                $eqUsd = (float)($item['eqUsd'] ?? 0);
                $rate = $eq > 0 ? $eqUsd / $eq : null;

                $params = [
                    'user_id' => $userId,
                    'ccy' => $item['ccy'],
                    'eq' => $eq,
                    'eq_usd' => $eqUsd,
                    'rate' => $rate,
                ];

                if (!$useNow) {
                    $params['created_at'] = $createdAt->format('Y-m-d H:i:s');
                }

                $stmt->execute($params);
            }
        } catch (PDOException $e) {
            $this->logger->error("DB error when saving balances", ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw new RuntimeException('Failed to save balances to database: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getBalancesByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT ccy, eq, eq_usd, rate, created_at FROM user_balances WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
