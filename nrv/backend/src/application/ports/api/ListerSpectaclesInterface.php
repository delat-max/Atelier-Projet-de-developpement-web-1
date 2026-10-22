<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface ListerSpectaclesInterface
{
    public function execute(?string $date = null, ?string $genre = null, ?string $tri = null, ?int $limit = null, ?int $offset = null): array;

    public function count(?string $date = null, ?string $genre = null): int;

    public function dates(): array;

    public function genres(): array;
}
