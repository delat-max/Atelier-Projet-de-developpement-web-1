<?php

declare(strict_types=1);

namespace App\adapters\http;

use App\application\ports\spi\CommandeRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BilletController
{
    public function __construct(
        private CommandeRepositoryInterface $commandeRepository
    ) {
    }

    public function obtenir(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $token = $request->getAttribute('token');

        if (
            !is_string($token)
            || !preg_match('/\A[a-f0-9]{64}\z/', $token)
        ) {
            return $this->json($response, [
                'error' => 'Billet introuvable',
            ], 404);
        }

        $billet = $this->commandeRepository
            ->obtenirBilletParToken($token);

        if ($billet === null) {
            return $this->json($response, [
                'error' => 'Billet introuvable',
            ], 404);
        }

        return $this->json($response, [
            'data' => $billet,
        ]);
    }

    public function mesBillets(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $idUtilisateur = $_SESSION['user_id'] ?? null;

        if (!is_int($idUtilisateur) || $idUtilisateur < 1) {
            return $this->json($response, [
                'error' => 'Authentification requise',
            ], 401);
        }

        $billets = $this->commandeRepository
            ->obtenirBilletsUtilisateur($idUtilisateur);

        return $this->json($response, [
            'data' => $billets,
        ]);
    }

    private function json(
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write(
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        );

        return $response
            ->withStatus($status)
            ->withHeader(
                'Content-Type',
                'application/json; charset=utf-8'
            )
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}