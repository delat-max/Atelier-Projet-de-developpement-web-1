<?php

declare(strict_types=1);

use App\CatalogRepository;
use Slim\Factory\AppFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';
$db = $settings['db'];
$pdo = new PDO($db['dsn'], $db['user'], $db['password'], [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
]);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$registerRoutes = require __DIR__ . '/routes.php';
$registerRoutes($app, new CatalogRepository($pdo));

return $app;
