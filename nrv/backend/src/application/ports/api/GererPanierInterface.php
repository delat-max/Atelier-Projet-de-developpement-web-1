<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface GererPanierInterface
{
    public function obtenir(int $idUtilisateur): array;

    public function synchroniser(int $idUtilisateur, array $articles): array;
}