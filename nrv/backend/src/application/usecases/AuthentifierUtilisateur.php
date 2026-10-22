<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\AuthentifierUtilisateurInterface;
use App\application\ports\spi\UtilisateurRepositoryInterface;

final class AuthentifierUtilisateur implements AuthentifierUtilisateurInterface
{
    public function __construct(
        private UtilisateurRepositoryInterface $repository
    ) {
    }

    public function execute(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        $utilisateur = $this->repository->findByEmail($email);

        if (
            $utilisateur === null ||
            !password_verify($password, $utilisateur['password'])
        ) {
            return null;
        }

        if (
            password_needs_rehash(
                $utilisateur['password'],
                PASSWORD_DEFAULT
            )
        ) {
            $this->repository->updatePassword(
                (int) $utilisateur['id_utilisateur'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        unset($utilisateur['password']);

        return $utilisateur;
    }
}