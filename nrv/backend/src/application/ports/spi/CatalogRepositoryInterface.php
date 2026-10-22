<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface CatalogRepositoryInterface
{
    public function findAllSpectacles(?string $date = null, ?string $genre = null, ?string $tri = null, ?int $limit = null, ?int $offset = null): array;

    public function countAllSpectacles(?string $date = null, ?string $genre = null): int;

    public function findAvailableDates(): array;

    public function findAvailableGenres(): array;

    public function findSoireeById(int $id): ?array;

    public function findSpectaclesBySoireeId(int $id): array;
}
