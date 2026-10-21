<?php

declare(strict_types=1);

use App\adapters\http\CatalogController;
use App\adapters\persistence\MySQLCatalogRepository;
use App\application\usecases\ListerSpectacles;
use App\application\usecases\ObtenirDetailsSoiree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

require dirname(__DIR__) . '/vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';
$db = $settings['db'];
$pdo = new PDO($db['dsn'], $db['user'], $db['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$repository = new MySQLCatalogRepository($pdo);
$listerSpectacles = new ListerSpectacles($repository);
$obtenirDetailsSoiree = new ObtenirDetailsSoiree($repository);
$controller = new CatalogController($listerSpectacles, $obtenirDetailsSoiree);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(false, true, false);
$errorMiddleware->setDefaultErrorHandler(static function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app): ResponseInterface {
    $status = $exception instanceof HttpNotFoundException ? 404 : 500;
    $payload = ['error' => $status === 404 ? 'Route introuvable' : 'Erreur interne du serveur'];
    $response = $app->getResponseFactory()->createResponse($status);
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('Referrer-Policy', 'no-referrer')
        ->withHeader('Cache-Control', 'no-store');
});

$app->add(static function (ServerRequestInterface $request, $handler): ResponseInterface {
    $response = $handler->handle($request);

    return $response
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('Referrer-Policy', 'no-referrer')
        ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

$registerRoutes = require __DIR__ . '/routes.php';
$registerRoutes($app, $controller);

return $app;
