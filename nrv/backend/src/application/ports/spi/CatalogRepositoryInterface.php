<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface CatalogRepositoryInterface
{
    public function findAllSpectacles(?string $date = null, ?string $genre = null, ?string $tri = null): array;

    public function findSoireeById(int $id): ?array;

    public function findSpectaclesBySoireeId(int $id): array;
}
