<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class HttpTestCase extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    protected function connecter(int $idUtilisateur): void
    {
        $_SESSION['user_id'] = $idUtilisateur;
    }

    protected function requete(string $methode = 'GET', array $query = [], ?array $body = null): ServerRequestInterface
    {
        $requete = (new ServerRequestFactory())
            ->createServerRequest($methode, '/api/test')
            ->withQueryParams($query);

        return $body === null ? $requete : $requete->withParsedBody($body);
    }

    protected function reponse(): ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }

    protected function json(ResponseInterface $reponse): array
    {
        return json_decode((string) $reponse->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
