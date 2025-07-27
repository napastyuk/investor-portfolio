<?php

namespace App\Interface\Http;

use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



class BalanceController
{
    public function __construct(
        private OkxClientInterface $okxClient,
        private BalanceRepositoryInterface $balanceRepository,
    ) {}

    /**
     * @OA\Post(
     *     path="/balances/import",
     *     summary="Импортировать балансы из OKX",
     *     tags={"Balances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Балансы импортированы")
     * )
     */
    public function import(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->okxClient->fetchBalances($userId);
        $this->balanceRepository->saveBalances($userId, $balances);

        $response->getBody()->write(json_encode(['status' => 'success', 'balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @OA\Get(
     *     path="/balances",
     *     summary="Получить список балансов пользователя",
     *     tags={"Balances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Список балансов")
     * )
     */
    public function list(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->balanceRepository->getBalancesByUserId($userId);

        $response->getBody()->write(json_encode(['balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
