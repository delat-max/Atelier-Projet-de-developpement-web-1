<?php

declare(strict_types=1);

namespace App\adapters\http;

use App\application\ports\api\ListerSpectaclesInterface;
use App\application\ports\api\ObtenirDetailsSoireeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CatalogController
{
    public function __construct(
        private ListerSpectaclesInterface $listerSpectacles,
        private ObtenirDetailsSoireeInterface $obtenirDetailsSoiree
    ) {
    }

    public function listerSpectacles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $date = isset($query['date']) && is_string($query['date']) ? trim($query['date']) : null;
        $genre = isset($query['genre']) && is_string($query['genre']) ? trim($query['genre']) : null;
        $tri = isset($query['tri']) && is_string($query['tri']) ? trim($query['tri']) : null;

        if ($date !== null && $date !== '' && !$this->dateValide($date)) {
            return $this->json($response, ['error' => 'Date invalide'], 400);
        }

        if ($genre !== null && strlen($genre) > 240) {
            return $this->json($response, ['error' => 'Style de musique invalide'], 400);
        }

        if ($tri !== null && !in_array($tri, ['date-croissante', 'date-decroissante', 'titre'], true)) {
            return $this->json($response, ['error' => 'Tri invalide'], 400);
        }

        return $this->json($response, [
            'data' => $this->listerSpectacles->execute($date ?: null, $genre ?: null, $tri),
        ]);
    }

    public function detailsSoiree(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            return $this->json($response, ['error' => 'Identifiant de soirée invalide'], 400);
        }

        $soiree = $this->obtenirDetailsSoiree->execute($id);

        if ($soiree === null) {
            return $this->json($response, ['error' => 'Soirée introuvable'], 404);
        }

        return $this->json($response, ['data' => $soiree]);
    }

    private function dateValide(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'no-store');
    }
}
