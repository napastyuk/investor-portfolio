<?php declare(strict_types=1);

namespace App\Interface\Http;

use OpenApi\Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nyholm\Psr7\Response;

final class SwaggerController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // Указываем директории, которые будут сканироваться
        $openapi = Generator::scan([
            __DIR__,
            dirname(__DIR__, 2) . '/Application',
            dirname(__DIR__, 2) . '/Domain',
            dirname(__DIR__, 2) . '/Infrastructure',
        ]);

        $response = new Response();
        $response->getBody()->write($openapi->toJson());

        return $response->withHeader('Content-Type', 'application/json');
    }
}
