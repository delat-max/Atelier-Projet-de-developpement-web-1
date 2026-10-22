<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\InscrireUtilisateurInterface;
use App\application\ports\spi\UtilisateurRepositoryInterface;
use DomainException;

final class InscrireUtilisateur implements InscrireUtilisateurInterface
{
    public function __construct(
        private UtilisateurRepositoryInterface $repository
    ) {
    }

    public function execute(
        string $nom,
        string $prenom,
        string $email,
        string $password
    ): int {
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

        if (strlen($password) < 10 || strlen($password) > 4096) {
            throw new DomainException('mot_de_passe_invalide');
        }

        if ($this->repository->findByEmail($email) !== null) {
            throw new DomainException('email_deja_utilise');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return $this->repository->create(
            $nom,
            $prenom,
            $email,
            $passwordHash
        );
    }
}