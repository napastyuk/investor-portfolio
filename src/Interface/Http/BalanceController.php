<?php declare(strict_types=1);

namespace App\Interface\Http;

use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;
use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;

class BalanceController
{
    public function __construct(
        private OkxClientInterface $okxClient,
        private BalanceRepositoryInterface $balanceRepository,
        private LoggerInterface $logger,
    ) {}
    
    #[OA\Post(
        path: '/balances/import',
        summary: 'Импорт балансов с OKX API и сохранение в БД',
        security: [['bearerAuth' => []]],
        tags: ['Balances'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Балансы успешно импортированы'
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка валидации или невозможность получить данные'
            ),
            new OA\Response(
                response: 401,
                description: 'Пользователь не авторизован'
            )
        ]
    )]
    public function import(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        try {
            $balances = $this->okxClient->fetchBalances($userId);
            $createdAt = new DateTimeImmutable();
            $this->balanceRepository->saveBalance($userId, $balances, $createdAt);
            $this->logger->info("Balances saved successfully from OKX", ['user_id' => $userId, 'count' => count($balances)]);
            $response->getBody()->write(json_encode(['status' => 'success', 'balances' => $balances]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $e) {
            $this->logger->error("Failed to import balances", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to import balances',
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    #[OA\Get(
        path: '/balances',
        summary: 'Получить список балансов пользователя',
        security: [['bearerAuth' => []]],
        tags: ['Balances'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список балансов пользователя',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'balances', type: 'array', items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'ccy', type: 'string', example: 'BTC'),
                                new OA\Property(property: 'eq', type: 'string', example: '0.123'),
                                new OA\Property(property: 'eq_usd', type: 'string', example: '3200.50'),
                                new OA\Property(property: 'rate', type: 'string', example: '26000.00'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                            ]
                        ))
                    ]
                )
            )
        ]
    )]
    public function list(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->balanceRepository->getBalancesByUserId($userId);

        $response->getBody()->write(json_encode(['balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
