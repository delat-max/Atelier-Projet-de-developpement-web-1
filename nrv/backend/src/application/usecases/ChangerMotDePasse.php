<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ChangerMotDePasseInterface;
use App\application\ports\spi\UtilisateurRepositoryInterface;
use DomainException;

final class ChangerMotDePasse implements ChangerMotDePasseInterface
{
    public function __construct(
        private UtilisateurRepositoryInterface $repository
    ) {
    }

    public function execute(
        int $id,
        string $currentPassword,
        string $newPassword
    ): bool {
        $utilisateur = $this->repository->findById($id);

        if (
            $utilisateur === null ||
            !password_verify(
                $currentPassword,
                $utilisateur['password']
            )
        ) {
            return false;
        }

        if (
            strlen($newPassword) < 10 ||
            strlen($newPassword) > 4096
        ) {
            throw new DomainException('mot_de_passe_invalide');
        }

        $this->repository->updatePassword(
            $id,
            password_hash($newPassword, PASSWORD_DEFAULT)
        );

        return true;
    }
}