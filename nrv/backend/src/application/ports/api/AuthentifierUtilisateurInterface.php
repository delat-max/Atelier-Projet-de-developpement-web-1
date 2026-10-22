<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface AuthentifierUtilisateurInterface
{
    public function execute(string $email, string $password): ?array;
}