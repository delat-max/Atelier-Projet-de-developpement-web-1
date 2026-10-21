<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ObtenirDetailsSoireeInterface;
use App\application\ports\spi\CatalogRepositoryInterface;

final class ObtenirDetailsSoiree implements ObtenirDetailsSoireeInterface
{
    public function __construct(private CatalogRepositoryInterface $catalog)
    {
    }

    public function execute(int $id): ?array
    {
        $soiree = $this->catalog->findSoireeById($id);

        if ($soiree === null) {
            return null;
        }

        $soiree['spectacles'] = $this->catalog->findSpectaclesBySoireeId($id);

        return $soiree;
    }
}
