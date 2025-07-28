<?php declare(strict_types=1);

namespace App\Infrastructure\Redis;

interface SimpleRedisInterface
{
    public function get(string $key): ?string;
    public function setex(string $key, int $ttl, string $value): bool;
    public function multi(): void;
    public function incr(string $key): int;
    public function expire(string $key, int $seconds): bool;
    public function exec(): void;
}
