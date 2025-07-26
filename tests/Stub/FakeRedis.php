<?php

namespace Tests\Stub;

use App\Infrastructure\Redis\SimpleRedisInterface;

class FakeRedis implements SimpleRedisInterface
{
    private array $storage = [];
    private array $transaction = [];
    private bool $inTransaction = false;

    public function get(string $key): ?string
    {
        return $this->storage[$key] ?? null;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        $this->storage[$key] = $value;
        return true;
    }

    public function multi(): void
    {
        $this->inTransaction = true;
        $this->transaction = [];
    }

    public function incr(string $key): int
    {
        if ($this->inTransaction) {
            $this->transaction[] = fn() => $this->incr($key);
            return 0;
        }

        $value = isset($this->storage[$key]) ? (int)$this->storage[$key] : 0;
        $this->storage[$key] = $value + 1;
        return $this->storage[$key];
    }

    public function expire(string $key, int $seconds): bool
    {
        if ($this->inTransaction) {
            $this->transaction[] = fn() => $this->expire($key, $seconds);
            return true;
        }

        return true;
    }

    public function exec(): void
    {
        foreach ($this->transaction as $operation) {
            $operation();
        }

        $this->inTransaction = false;
        $this->transaction = [];
    }
}
