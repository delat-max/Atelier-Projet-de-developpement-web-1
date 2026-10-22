<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ModifierProfilInterface;
use App\application\ports\spi\UtilisateurRepositoryInterface;
use DomainException;

final class ModifierProfil implements ModifierProfilInterface
{
    public function __construct(
        private UtilisateurRepositoryInterface $repository
    ) {
    }

    public function execute(
        int $id,
        string $nom,
        string $prenom,
        string $email
    ): void {
        $nom = trim($nom);
        $prenom = trim($prenom);
        $email = strtolower(trim($email));

        if (
            $nom === '' ||
            strlen($nom) > 50 ||
            $prenom === '' ||
            strlen($prenom) > 50
        ) {
            throw new DomainException('nom_prenom_invalides');
        }

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 100
        ) {
            throw new DomainException('email_invalide');
        }

        $this->repository->updateProfile(
            $id,
            $nom,
            $prenom,
            $email
        );
    }
}