<?php

declare(strict_types=1);

namespace App\adapters\paiement;

use App\application\ports\spi\PaiementGatewayInterface;
use DomainException;

final class PaiementSimule implements PaiementGatewayInterface {
    public function payer(int $idCommande, float $montant): string {
        if ($idCommande < 1 || $montant <= 0) {
            throw new DomainException('paiement_invalide');
        }

        return 'SIM-' . strtoupper(bin2hex(random_bytes(8)));
    }

    public function rembourser(string $referencePaiement): void {}
}
