<?php declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

interface BalanceRepositoryInterface
{
    public function saveBalance(int $userId, array $balances, ?DateTimeImmutable $createdAt = null): void;

    public function getBalancesByUserId(int $userId): array;
}
