<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\App;

abstract class IntegrationTestCase extends TestCase
{
    protected App $app;
    protected \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Подключаем Slim-приложение
        $this->app = require __DIR__ . '/../../config/bootstrap.php';

        // Подключение к тестовой PostgreSQL
        $this->pdo = new \PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_PORT') ?: '6543',
                getenv('DB_NAME') ?: 'test_db'
            ),
            getenv('DB_USER') ?: 'test_user',
            getenv('DB_PASS') ?: 'secret'
        );

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Очистка таблиц
        $this->pdo->exec("TRUNCATE TABLE user_balances RESTART IDENTITY CASCADE");
        $this->pdo->exec("TRUNCATE TABLE users RESTART IDENTITY CASCADE");
    }
}
