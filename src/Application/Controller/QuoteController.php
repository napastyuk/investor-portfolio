<?php

namespace App\Application\Controller;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class QuoteController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $client = new \GuzzleHttp\Client();
        $apiResponse = $client->get('https://dummyjson.com/quotes');
        $data = json_decode($apiResponse->getBody(), true);

        $stmt = $this->pdo->prepare('INSERT INTO quotes (quote, author) VALUES (:quote, :author)');

        foreach ($data['quotes'] as $quote) {
            $stmt->execute([
                'quote' => $quote['quote'],
                'author' => $quote['author']
            ]);
        }

        $response->getBody()->write(json_encode(['inserted' => count($data['quotes'])]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
