<?php declare(strict_types=1);

namespace App\Interface\Http;

use App\Application\Service\OkxClientInterface;
use App\Domain\Repository\BalanceRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;
use Psr\Log\LoggerInterface;

class BalanceController
{
    public function __construct(
        private OkxClientInterface $okxClient,
        private BalanceRepositoryInterface $balanceRepository,
        private LoggerInterface $logger,
    ) {}

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

    public function list(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $userId = $user['id'] ?? 0;

        $balances = $this->balanceRepository->getBalancesByUserId($userId);

        $response->getBody()->write(json_encode(['balances' => $balances]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
