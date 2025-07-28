<?php declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\BalanceRepositoryInterface;
use DateTimeImmutable;
use PDO;

class BalanceRepository implements BalanceRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function saveAll(int $userId, array $balances, DateTimeImmutable $createdAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_balances (user_id, ccy, eq, eq_usd, rate, created_at)
            VALUES (:user_id, :ccy, :eq, :eq_usd, :rate, :created_at)
            ON CONFLICT (user_id, ccy) DO UPDATE SET
                eq = EXCLUDED.eq,
                eq_usd = EXCLUDED.eq_usd,
                rate = EXCLUDED.rate,
                created_at = EXCLUDED.created_at'
        );

        foreach ($balances as $item) {
            $eq = (float)($item['eq'] ?? 0);
            $eqUsd = (float)($item['eqUsd'] ?? 0);
            $rate = $eq > 0 ? $eqUsd / $eq : null;

            $stmt->execute([
                'user_id' => $userId,
                'ccy' => $item['ccy'],
                'eq' => $eq,
                'eq_usd' => $eqUsd,
                'rate' => $rate,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM user_balances WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveBalances(int $userId, array $balances): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_balances (user_id, ccy, eq, eq_usd, rate, created_at)
            VALUES (:user_id, :ccy, :eq, :eq_usd, :rate, NOW())
            ON CONFLICT (user_id, ccy) DO UPDATE SET
                eq = EXCLUDED.eq,
                eq_usd = EXCLUDED.eq_usd,
                rate = EXCLUDED.rate,
                created_at = EXCLUDED.created_at'
        );

        foreach ($balances as $item) {
            $eq = (float)($item['eq'] ?? 0);
            $eqUsd = (float)($item['eqUsd'] ?? 0);
            $rate = $eq > 0 ? $eqUsd / $eq : null;

            $stmt->execute([
                'user_id' => $userId,
                'ccy' => $item['ccy'],
                'eq' => $eq,
                'eq_usd' => $eqUsd,
                'rate' => $rate,
            ]);
        }
    }

    public function getBalancesByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT ccy, eq, eq_usd, rate, created_at FROM user_balances WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
