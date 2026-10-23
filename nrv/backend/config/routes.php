<?php

declare(strict_types=1);

use App\adapters\http\CatalogController;
use App\adapters\http\UtilisateurController;
use App\adapters\http\PanierController;
use App\adapters\http\CommandeController;
use App\adapters\http\BilletController;
use Slim\App;

return static function (
    App $app,
    CatalogController $catalogController,
    UtilisateurController $utilisateurController,
    PanierController $panierController,
    CommandeController $commandeController,
    BilletController $billetController
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

    $app->get(
        '/api/panier', 
        [$panierController, 'obtenir']
    );

    $app->put(
        '/api/panier', 
        [$panierController, 'synchroniser']
    );

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
};