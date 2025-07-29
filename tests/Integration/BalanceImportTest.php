<?php declare(strict_types=1);

namespace Tests\Integration;

use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class BalanceImportTest extends IntegrationTestCase
{
    public function testImportBalancesStoresData(): void
    {
        // Создаём тестового пользователя
        $this->pdo->exec("
            INSERT INTO users (id, name, token, okx_api_key, okx_secret_key, okx_passphrase)
            VALUES (1, 'Test User', 'test-token', 'key', 'secret', 'passphrase')
        ");

        // Мокаем OkxClientInterface
        $mockClient = $this->createMock(\App\Application\Service\OkxClientInterface::class);
        $mockClient->method('fetchBalances')->willReturn([
            ['ccy' => 'BTC', 'eq' => '0.5', 'eqUsd' => '30000', 'rate' => '60000'],
        ]);

        // Внедряем мок в контейнер
        $container = $this->app->getContainer();
        $container->set(\App\Application\Service\OkxClientInterface::class, $mockClient);

        // Создаём запрос
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/balances/import')
            ->withHeader('Authorization', 'Bearer test-token');

        $response = $this->app->handle($request);

        // Проверка статуса
        $this->assertEquals(200, $response->getStatusCode());

        // Проверка записи в user_balances
        $stmt = $this->pdo->query("SELECT * FROM user_balances WHERE user_id = 1 AND ccy = 'BTC'");
        $balance = $stmt->fetch();

        $this->assertNotFalse($balance);
        $this->assertEqualsWithDelta(0.5, (float) $balance['eq'], 0.00001);
        $this->assertEquals('30000.00', $balance['eq_usd']);
        $this->assertEquals('60000.0000000000', $balance['rate']);
    }
}
