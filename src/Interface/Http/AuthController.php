<?php

namespace App\Interface\Http;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Interface\Http\Responder\JsonResponder;

readonly class AuthController
{
    public function __construct(private PDO $pdo, private JsonResponder $responder) {}

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();

        foreach (['name', 'okx_api_key', 'okx_secret_key', 'okx_passphrase'] as $field) {
            if (empty($data[$field])) {
                return $this->responder->error($response, "Missing field: $field", 400);
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

        return $this->responder->success($response, [
            'id' => $this->pdo->lastInsertId(),
            'token' => $token
        ], 201);
    }
}
