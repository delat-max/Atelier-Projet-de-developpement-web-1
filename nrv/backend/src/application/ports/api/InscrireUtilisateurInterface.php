<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface InscrireUtilisateurInterface
{
    public function execute(
        string $nom,
        string $prenom,
        string $email,
        string $password
    ): int;
}