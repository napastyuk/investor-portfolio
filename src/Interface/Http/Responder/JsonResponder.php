<?php declare(strict_types=1);

namespace App\Interface\Http\Responder;

use Psr\Http\Message\ResponseInterface;

final readonly class JsonResponder
{
    public function success(
        ResponseInterface $response,
        mixed $data = null,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write((string)json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    public function error(ResponseInterface $response, string|array $error, int $code = 500): ResponseInterface
    {
        $payload = is_array($error) ? $error : ['error' => $error];

        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($code);
    }
}
