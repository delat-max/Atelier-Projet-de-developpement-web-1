<?php

declare(strict_types=1);

use App\adapters\http\CatalogController;
use App\adapters\http\UtilisateurController;
use Slim\App;

return static function (
    App $app,
    CatalogController $catalogController,
    UtilisateurController $utilisateurController
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
};