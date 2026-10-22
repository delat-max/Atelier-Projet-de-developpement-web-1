<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface UtilisateurRepositoryInterface
{
    public function create(
        string $nom,
        string $prenom,
        string $email,
        string $passwordHash
    ): int;

    public function findByEmail(string $email): ?array;

    public function findById(int $id): ?array;

    public function updateProfile(
        int $id,
        string $nom,
        string $prenom,
        string $email
    ): void;

    public function updatePassword(
        int $id,
        string $passwordHash
    ): void;
}
