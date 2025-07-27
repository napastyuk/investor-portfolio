<?php

namespace App\Interface\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SwaggerController
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $openapi = \OpenApi\Generator::scan([
            __DIR__ . '/../../OpenApi',
            __DIR__ . '/../../Interface/Http',
        ]);
        $response->getBody()->write($openapi->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    }
}
