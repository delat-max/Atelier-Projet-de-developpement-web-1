<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface ChangerMotDePasseInterface
{
    public function execute(
        int $id,
        string $currentPassword,
        string $newPassword
    ): bool;
}