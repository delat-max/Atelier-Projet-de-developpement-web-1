<?php

declare(strict_types=1);

namespace App\adapters\http;

use App\application\ports\api\GererPanierInterface;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PanierController
{
    public function __construct(
        private GererPanierInterface $gererPanier
    ) {
    }

    public function obtenir(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $userId = $this->userId();
        if ($userId === null) {
            return $this->json($response, ['error' => 'Authentification requise'], 401);
        }

        return $this->json($response, [
            'data' => $this->gererPanier->obtenir($userId),
        ]);
    }

    public function synchroniser(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $userId = $this->userId();
        if ($userId === null) {
            return $this->json($response, ['error' => 'Authentification requise'], 401);
        }

        $body = $request->getParsedBody();
        $articles = is_array($body) && isset($body['articles']) && is_array($body['articles'])
            ? $body['articles']
            : null;

        if ($articles === null) {
            return $this->json($response, ['error' => 'Format de panier invalide'], 400);
        }

        try {
            $panier = $this->gererPanier->synchroniser($userId, $articles);
        } catch (DomainException $e) {
            $msg = match ($e->getMessage()) {
                'soiree_introuvable' => 'Soirée introuvable',
                'places_insuffisantes' => 'Nombre de places disponibles insuffisant',
                default => 'Données du panier invalides',
            };
            return $this->json($response, ['error' => $msg], 400);
        }

        return $this->json($response, ['data' => $panier]);
    }

    private function userId(): ?int
    {
        return isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])
            ? $_SESSION['user_id']
            : null;
    }

    private function json(
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}