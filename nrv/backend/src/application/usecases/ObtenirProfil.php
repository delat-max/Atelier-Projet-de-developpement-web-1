<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ObtenirProfilInterface;
use App\application\ports\spi\UtilisateurRepositoryInterface;

final class ObtenirProfil implements ObtenirProfilInterface
{
    public function __construct(
        private UtilisateurRepositoryInterface $repository
    ) {
    }

    public function execute(int $id): ?array
    {
        $utilisateur = $this->repository->findById($id);

        if ($utilisateur !== null) {
            unset($utilisateur['password']);
        }

        return $utilisateur;
    }
}