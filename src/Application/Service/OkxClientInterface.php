<?php declare(strict_types=1);

namespace App\Application\Service;

interface OkxClientInterface
{
    public function getBalances(string $apiKey, string $secretKey, string $passphrase, bool $isSimulated = false): array;

    public function fetchBalances(int $userId): array;
}
