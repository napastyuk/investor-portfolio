<?php

namespace App\Domain\Repository;

use DateTimeImmutable;

interface BalanceRepositoryInterface
{
    public function saveAll(int $userId, array $balances, DateTimeImmutable $createdAt): void;

    public function getAll(int $userId): array;

    public function saveBalances(int $userId, array $balances): void;
    
    public function getBalancesByUserId(int $userId): array;
}
