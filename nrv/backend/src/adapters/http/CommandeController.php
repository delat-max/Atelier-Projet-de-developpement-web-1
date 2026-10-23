<?php

declare(strict_types=1);

namespace App\adapters\http;

use App\application\ports\api\ConfirmerCommandeInterface;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CommandeController {
    public function __construct(private ConfirmerCommandeInterface $confirmerCommande) {}

    public function confirmer(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $idUtilisateur = $this->userId();

        if ($idUtilisateur === null) {
            return $this->json($response, [
                'error' => 'Authentification requise',
            ], 401);
        }

        $idCommande = filter_var(
            $request->getAttribute('id'),
            FILTER_VALIDATE_INT
        );

        if ($idCommande === false || $idCommande < 1) {
            return $this->json($response, [
                'error' => 'Identifiant de commande invalide',
            ], 400);
        }

        try {
            $resultat = $this->confirmerCommande->executer(
                $idUtilisateur,
                $idCommande
            );

            return $this->json($response, [
                'data' => $resultat,
                'message' => 'Commande confirmée en mode simulation',
            ], 200);
        } catch (DomainException $e) {
            [$message, $status] = match ($e->getMessage()) {
                'commande_introuvable' => [
                    'Commande introuvable',
                    404,
                ],
                'commande_non_payable' => [
                    'Cette commande ne peut pas être payée',
                    409,
                ],
                'panier_vide' => [
                    'Le panier est vide',
                    400,
                ],
                'soiree_introuvable' => [
                    'Une soirée du panier est introuvable',
                    400,
                ],
                'places_insuffisantes' => [
                    'Il ne reste pas assez de places disponibles',
                    409,
                ],
                'montant_modifie' => [
                    'Le montant a changé. Veuillez vérifier votre panier',
                    409,
                ],
                'tarif_invalide' => [
                    'Type de tarif invalide',
                    400,
                ],
                'quantite_invalide' => [
                    'Quantité invalide',
                    400,
                ],
                'paiement_invalide' => [
                    'Montant ou commande invalide',
                    400,
                ],
                default => [
                    'Impossible de confirmer la commande',
                    400,
                ],
            };

            return $this->json($response, [
                'error' => $message,
            ], $status);
        }
    }

    private function userId(): ?int
    {
        return isset($_SESSION['user_id'])
            && is_int($_SESSION['user_id'])
                ? $_SESSION['user_id']
                : null;
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
