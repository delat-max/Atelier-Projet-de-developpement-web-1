<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface PanierRepositoryInterface
{
    public function obtenirPanierEnCours(int $idUtilisateur): array;

    public function synchroniserPanier(int $idUtilisateur, array $articles): array;
}