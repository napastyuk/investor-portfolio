<?php

namespace App\Interface\Http;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class AuthController
{
    public function __construct(private PDO $pdo) {}

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        foreach (['name', 'okx_api_key', 'okx_secret_key', 'okx_passphrase'] as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Missing field: $field"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $token = bin2hex(random_bytes(16));

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, token, okx_api_key, okx_secret_key, okx_passphrase)
             VALUES (:name, :token, :okx_api_key, :okx_secret_key, :okx_passphrase)'
        );

        $stmt->execute([
            'name' => $data['name'],
            'token' => $token,
            'okx_api_key' => $data['okx_api_key'],
            'okx_secret_key' => $data['okx_secret_key'],
            'okx_passphrase' => $data['okx_passphrase'],
        ]);

        $response->getBody()->write(json_encode([
            'id' => $this->pdo->lastInsertId(),
            'token' => $token
        ], JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
