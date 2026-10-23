<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface CommandeRepositoryInterface
{
    public function preparerPaiement(
        int $idUtilisateur,
        int $idCommande
    ): array;

    public function finaliserCommandeEtGenererBillets(
        int $idUtilisateur,
        int $idCommande,
        float $montantConfirme
    ): array;

    public function obtenirBilletParToken(
        string $token
    ): ?array;

    public function obtenirBilletsUtilisateur(
        int $idUtilisateur
    ): array;
}