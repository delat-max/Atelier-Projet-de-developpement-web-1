<?php

declare(strict_types=1);

use App\CatalogRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return static function (App $app, CatalogRepository $catalog): void {
    $json = static function (
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    };

    $app->get('/api/spectacles', static function (
        ServerRequestInterface $request,
        ResponseInterface $response
    ) use ($catalog, $json): ResponseInterface {
        return $json($response, ['data' => $catalog->findAllSpectacles()]);
    });

    $app->get('/api/soirees/{id}', static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ) use ($catalog, $json): ResponseInterface {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            return $json($response, ['error' => 'Identifiant de soirée invalide'], 400);
        }

        $soiree = $catalog->findSoireeById($id);
        if ($soiree === null) {
            return $json($response, ['error' => 'Soirée introuvable'], 404);
        }

        $soiree['spectacles'] = $catalog->findSpectaclesBySoireeId($id);

        return $json($response, ['data' => $soiree]);
    });
};
