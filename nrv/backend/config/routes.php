<?php

declare(strict_types=1);

use App\adapters\http\CatalogController;
use Slim\App;

return static function (App $app, CatalogController $controller): void {
    $app->get('/api/spectacles', [$controller, 'listerSpectacles']);
    $app->get('/api/soirees/{id}', [$controller, 'detailsSoiree']);
};
