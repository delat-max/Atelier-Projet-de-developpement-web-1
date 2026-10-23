<?php

declare(strict_types=1);

use App\adapters\http\AdminController;
use App\adapters\http\BilletController;
use App\adapters\http\CatalogController;
use App\adapters\http\CommandeController;
use App\adapters\http\PanierController;
use App\adapters\http\UtilisateurController;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (
    App $app,
    CatalogController $catalogController,
    UtilisateurController $utilisateurController,
    PanierController $panierController,
    CommandeController $commandeController,
    BilletController $billetController,
    AdminController $adminController,
    PDO $pdo,
    array $adminEmails
): void {
    $app->get('/api/dates', [$catalogController, 'dates']);
    $app->get('/api/genres', [$catalogController, 'genres']);

    $app->get(
        '/api/spectacles',
        [$catalogController, 'listerSpectacles']
    );

    $app->get(
        '/api/soirees/{id}',
        [$catalogController, 'detailsSoiree']
    );

    $app->post(
        '/api/utilisateurs',
        [$utilisateurController, 'inscrire']
    );

    $app->post(
        '/api/auth/connexion',
        [$utilisateurController, 'connexion']
    );

    $app->post(
        '/api/auth/deconnexion',
        [$utilisateurController, 'deconnexion']
    );

    $app->get(
        '/api/utilisateurs/me',
        [$utilisateurController, 'profil']
    );

    $app->patch(
        '/api/utilisateurs/me',
        [$utilisateurController, 'modifierProfil']
    );

    $app->patch(
        '/api/utilisateurs/me/mot-de-passe',
        [$utilisateurController, 'modifierMotDePasse']
    );

    $app->get('/api/panier', [$panierController, 'obtenir']);
    $app->put('/api/panier', [$panierController, 'synchroniser']);

    $app->post(
        '/api/commandes/{id}/paiement',
        [$commandeController, 'confirmer']
    );

    $app->get(
        '/api/billets/{token}',
        [$billetController, 'obtenir']
    );

    $app->get(
        '/api/utilisateurs/me/billets',
        [$billetController, 'mesBillets']
    );
    
    $adminOnly = static function (
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) use ($app, $pdo, $adminEmails): ResponseInterface {
        $userId = $_SESSION['user_id'] ?? null;

        if (!is_int($userId) || $userId < 1) {
            $response = $app->getResponseFactory()->createResponse(401);
            $response->getBody()->write(
                json_encode(
                    ['error' => 'Authentification requise'],
                    JSON_UNESCAPED_UNICODE
                )
            );

            return $response->withHeader(
                'Content-Type',
                'application/json; charset=utf-8'
            );
        }

        $stmt = $pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email
             FROM utilisateur
             WHERE id_utilisateur = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur === false) {
            $response = $app->getResponseFactory()->createResponse(401);
            $response->getBody()->write(
                json_encode(['error' => 'Utilisateur introuvable'])
            );

            return $response->withHeader(
                'Content-Type',
                'application/json; charset=utf-8'
            );
        }

        $email = mb_strtolower(trim($utilisateur['email']));
        $emailsAutorises = array_map(
            static fn (string $value): string =>
                mb_strtolower(trim($value)),
            $adminEmails
        );

        if (!in_array($email, $emailsAutorises, true)) {
            $response = $app->getResponseFactory()->createResponse(403);
            $response->getBody()->write(
                json_encode(
                    ['error' => 'Accès réservé à l’administrateur'],
                    JSON_UNESCAPED_UNICODE
                )
            );

            return $response->withHeader(
                'Content-Type',
                'application/json; charset=utf-8'
            );
        }

        $utilisateur['id_utilisateur'] =
            (int) $utilisateur['id_utilisateur'];

        return $handler->handle(
            $request->withAttribute('admin_user', $utilisateur)
        );
    };

    $app->group(
        '/api/admin',
        function (RouteCollectorProxy $group) use (
            $adminController
        ): void {
            $group->get('/me', [$adminController, 'me']);

            $group->get(
                '/tableau-de-bord',
                [$adminController, 'statistiques']
            );

            $group->get(
                '/soirees',
                [$adminController, 'soirees']
            );

            $group->post(
                '/soirees',
                [$adminController, 'creerSoiree']
            );

            $group->get(
                '/artistes',
                [$adminController, 'artistes']
            );

            $group->post(
                '/spectacles',
                [$adminController, 'ajouterSpectacle']
            );
        }
    )->add($adminOnly);
};