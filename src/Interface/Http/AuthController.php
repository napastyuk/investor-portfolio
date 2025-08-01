<?php

declare(strict_types=1);

namespace App\Interface\Http;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Interface\Http\Responder\JsonResponder;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;

readonly class AuthController
{
    public function __construct(
        private PDO $pdo,
        private JsonResponder $responder,
        private LoggerInterface $logger
    ) {}

    #[OA\Post(
        path: '/register',
        summary: 'Регистрация нового пользователя с OKX-ключами',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'okx_api_key', 'okx_secret_key', 'okx_passphrase'],
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'IlyaDemo'),
                    new OA\Property(property: 'okx_api_key', type: 'string', example: '1234545-123234-123243-12234124-12324234'),
                    new OA\Property(property: 'okx_secret_key', type: 'string', example: 'E22663CD5FASERAF558026A1654C8C'),
                    new OA\Property(property: 'okx_passphrase', type: 'string', example: 'seg3X$zwDHYg'),
                    new OA\Property(property: 'is_test_user', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Пользователь успешно зарегистрирован',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 42),
                        new OA\Property(property: 'token', type: 'string', example: 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка: отсутствует одно из обязательных полей'
            ),
            new OA\Response(
                response: 500,
                description: 'Ошибка сервера при регистрации пользователя'
            )
        ]
    )]
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        foreach (['name', 'okx_api_key', 'okx_secret_key', 'okx_passphrase'] as $field) {
            if (empty($data[$field])) {
                return $this->responder->error($response, "Missing field: $field", 400);
            }
        }

        $token = bin2hex(random_bytes(16));

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (name, token, okx_api_key, okx_secret_key, okx_passphrase, is_test_user)
                 VALUES (:name, :token, :okx_api_key, :okx_secret_key, :okx_passphrase, :is_test_user)'
            );

            $isTestUser = !empty($data['is_test_user']) ? (bool)$data['is_test_user'] : false;

            $stmt->execute([
                'name' => $data['name'],
                'token' => $token,
                'okx_api_key' => $data['okx_api_key'],
                'okx_secret_key' => $data['okx_secret_key'],
                'okx_passphrase' => $data['okx_passphrase'],
                'is_test_user' => $isTestUser,
            ]);

            return $this->responder->success($response, [
                'id' => $this->pdo->lastInsertId(),
                'token' => $token
            ], 201);

        } catch (\PDOException $e) {
            $this->logger->error('Ошибка при регистрации пользователя', [
                'name' => $data['name'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->responder->error($response, 'Ошибка при регистрации пользователя', 500);
        }
    }
}