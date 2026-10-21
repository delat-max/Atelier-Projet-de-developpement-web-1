<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ListerSpectaclesInterface;
use App\application\ports\spi\CatalogRepositoryInterface;

final class ListerSpectacles implements ListerSpectaclesInterface
{
    public function __construct(private CatalogRepositoryInterface $catalog)
    {
    }

    public function execute(?string $date = null, ?string $genre = null, ?string $tri = null): array
    {
        return $this->catalog->findAllSpectacles($date, $genre, $tri);
    }
}
