<?php

namespace App\Interface\Http;

use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Predis\Client as PredisClient;

class BalanceController
{
    public function __construct(
        private OkxClientInterface $okxClient,
        private BalanceRepositoryInterface $balanceRepository,
    ) {}

    public function import(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->okxClient->fetchBalances($userId);
        $this->balanceRepository->saveBalances($userId, $balances);

        $response->getBody()->write(json_encode(['status' => 'success', 'balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function list(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->balanceRepository->getBalancesByUserId($userId);

        $response->getBody()->write(json_encode(['balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
