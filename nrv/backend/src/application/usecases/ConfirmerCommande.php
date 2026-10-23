<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\ConfirmerCommandeInterface;
use App\application\ports\spi\CommandeRepositoryInterface;
use App\application\ports\spi\PaiementGatewayInterface;
use Throwable;

final class ConfirmerCommande implements ConfirmerCommandeInterface {
    public function __construct(private CommandeRepositoryInterface $commandeRepository, private PaiementGatewayInterface $paiementGateway) {}

    public function executer(int $idUtilisateur, int $idCommande): array {
        $commande = $this->commandeRepository->preparerPaiement(
            $idUtilisateur,
            $idCommande
        );

        $montant = (float) $commande['montant_total'];

        $referencePaiement = $this->paiementGateway->payer(
            $idCommande,
            $montant
        );

        try {
            $resultat = $this->commandeRepository
                ->finaliserCommandeEtGenererBillets(
                    $idUtilisateur,
                    $idCommande,
                    $montant
                );
            $resultat['reference_paiement'] = $referencePaiement;

            return $resultat;
        } catch (Throwable $e) {
            try {
                $this->paiementGateway->rembourser(
                    $referencePaiement
                );
            } catch (Throwable $erreurRemboursement) {}
            throw $e;
        }
    }
}
