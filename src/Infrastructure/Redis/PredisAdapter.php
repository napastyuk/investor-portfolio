<?php

namespace App\Infrastructure\Redis;

use Predis\Client;

class PredisAdapter implements SimpleRedisInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get(string $key): ?string
    {
        $result = $this->client->get($key);

        // get возвращает либо string либо null (если ключ отсутствует)
        return is_string($result) ? $result : null;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        $result = $this->client->setex($key, $ttl, $value);

        // setex возвращает Predis\Response\Status, который ведёт себя как true/false
        // Можно явно проверить и вернуть bool
        return $result instanceof \Predis\Response\Status && strtolower($result->getPayload()) === 'ok';
    }

    public function multi(): void
    {
        $this->client->multi();
    }

    public function incr(string $key): int
    {
        $result = $this->client->incr($key);

        // если результат не int (например, транзакция), возвращаем 0
        return is_int($result) ? $result : 0;
    }

    public function expire(string $key, int $seconds): bool
    {
        $result = $this->client->expire($key, $seconds);

        // expire возвращает 1 или 0 (int), или Status в транзакции
        // приводим к bool
        if (is_bool($result)) {
            return $result;
        }
        if (is_int($result)) {
            return $result === 1;
        }
        if ($result instanceof \Predis\Response\Status) {
            // в транзакции может вернуть Status, считаем что успешно
            return true;
        }
        return false;
    }

    public function exec(): void
    {
        $this->client->exec();
    }
}
