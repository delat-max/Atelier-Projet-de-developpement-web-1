<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface ObtenirDetailsSoireeInterface
{
    public function execute(int $id): ?array;
}
