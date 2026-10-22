<?php

declare(strict_types=1);

use App\adapters\http\CatalogController;
use App\adapters\http\UtilisateurController;
use App\adapters\persistence\MySQLCatalogRepository;
use App\adapters\persistence\MySQLUtilisateurRepository;
use App\application\usecases\ListerSpectacles;
use App\application\usecases\ObtenirDetailsSoiree;
use App\application\usecases\InscrireUtilisateur;
use App\application\usecases\AuthentifierUtilisateur;
use App\application\usecases\ObtenirProfil;
use App\application\usecases\ModifierProfil;
use App\application\usecases\ChangerMotDePasse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

require dirname(__DIR__) . '/vendor/autoload.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_name('NRVSESSID');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => filter_var(
        getenv('SESSION_SECURE') ?: '0',
        FILTER_VALIDATE_BOOLEAN
    ),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

$settings = require __DIR__ . '/settings.php';
$db = $settings['db'];

$pdo = new PDO(
    $db['dsn'],
    $db['user'],
    $db['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

$catalogRepository = new MySQLCatalogRepository($pdo);

$listerSpectacles = new ListerSpectacles($catalogRepository);
$obtenirDetailsSoiree = new ObtenirDetailsSoiree($catalogRepository);

$catalogController = new CatalogController(
    $listerSpectacles,
    $obtenirDetailsSoiree
);

$utilisateurRepository = new MySQLUtilisateurRepository($pdo);

$utilisateurController = new UtilisateurController(
    new InscrireUtilisateur($utilisateurRepository),
    new AuthentifierUtilisateur($utilisateurRepository),
    new ObtenirProfil($utilisateurRepository),
    new ModifierProfil($utilisateurRepository),
    new ChangerMotDePasse($utilisateurRepository)
);

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(false, true, false);

$errorMiddleware->setDefaultErrorHandler(
    static function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app): ResponseInterface {
        $status = $exception instanceof HttpNotFoundException ? 404 : 500;

        $payload = [
            'error' => $status === 404
                ? 'Route introuvable'
                : 'Erreur interne du serveur'
        ];

        $response = $app->getResponseFactory()->createResponse($status);

        $response->getBody()->write(
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        );

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader('Cache-Control', 'no-store');
    }
);

$app->add(
    static function (
        ServerRequestInterface $request,
        $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'no-referrer')
            ->withHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()'
            );
    }
);

$registerRoutes = require __DIR__ . '/routes.php';

$registerRoutes(
    $app,
    $catalogController,
    $utilisateurController
);

return $app;