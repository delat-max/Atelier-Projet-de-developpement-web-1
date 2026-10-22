<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface ModifierProfilInterface
{
    public function execute(
        int $id,
        string $nom,
        string $prenom,
        string $email
    ): void;
}