<?php

declare(strict_types=1);

namespace App\application\ports\api;

interface ConfirmerCommandeInterface {
    public function executer(int $idUtilisateur, int $idCommande): array;
}
