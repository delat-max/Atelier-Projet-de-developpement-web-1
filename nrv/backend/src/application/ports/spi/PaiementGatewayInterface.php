<?php

declare(strict_types=1);

namespace App\application\ports\spi;

interface PaiementGatewayInterface {
    public function payer(int $idCommande, float $montant): string;

    public function rembourser(string $referencePaiement): void;
}
