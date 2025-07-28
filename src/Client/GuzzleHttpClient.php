<?php declare(strict_types=1);

namespace App\Client;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(private Client $client) {}

    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $uri, $options);
    }
}
