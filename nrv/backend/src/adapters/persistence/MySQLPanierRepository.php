<?php

declare(strict_types=1);

namespace App\adapters\persistence;

use App\application\ports\spi\PanierRepositoryInterface;
use DomainException;
use PDO;

final class MySQLPanierRepository implements PanierRepositoryInterface
{
    private const TAUX_REDUIT = 0.80;
    private const MULTIPLICATEUR_PASS_3J = 3 * 0.97;
    private const ID_SOIREE_REFERENCE_PASS = 1;
    private const PLACES_PASS_AFFICHAGE = 999999;

    public function __construct(private PDO $pdo)
    {
    }

    public function obtenirPanierEnCours(int $idUtilisateur): array
    {
        $stmtCommande = $this->pdo->prepare(
            "SELECT id_commande, montant_total, statut
             FROM commande
             WHERE id_utilisateur = :id_utilisateur
               AND statut = 'en_cours'
             ORDER BY id_commande DESC
             LIMIT 1"
        );

        $stmtCommande->execute([
            'id_utilisateur' => $idUtilisateur,
        ]);

        $commande = $stmtCommande->fetch();

        if ($commande === false) {
            return [
                'id_commande' => null,
                'statut' => 'en_cours',
                'montant_total' => 0.0,
                'articles' => [],
            ];
        }

        $idCommande = (int) $commande['id_commande'];

        $stmtPanier = $this->pdo->prepare(
            'SELECT p.id_panier, p.id_soiree, p.type_tarif, p.quantite,
                    s.nom, s.`date`, s.lieu, s.prix AS prix_base,
                    s.places_disponibles
             FROM panier p
             INNER JOIN soiree s ON s.id_soiree = p.id_soiree
             WHERE p.id_commande = :id_commande
             ORDER BY p.id_panier ASC'
        );

        $stmtPanier->execute([
            'id_commande' => $idCommande,
        ]);

        $articles = [];
        $montantCalcule = 0.0;

        while ($row = $stmtPanier->fetch()) {
            $typeTarif = (string) $row['type_tarif'];
            $estPass = $this->estPass($typeTarif);

            $prixBase = $estPass
                ? $this->obtenirPrixBasePass()
                : (float) $row['prix_base'];

            $prixUnitaire = $this->calculerPrixUnitaire(
                $prixBase,
                $typeTarif
            );

            $quantite = (int) $row['quantite'];
            $montantCalcule += $prixUnitaire * $quantite;

            $articles[] = [
                'id_panier' => (int) $row['id_panier'],

                'id_soiree' => (int) $row['id_soiree'],

                'nom' => $estPass
                    ? 'Pass 3 jours'
                    : $row['nom'],

                'date' => $estPass ? '' : $row['date'],
                'lieu' => $estPass ? '' : $row['lieu'],

                'type_tarif' => $typeTarif,
                'prix_base' => $prixBase,
                'prix' => $prixUnitaire,
                'quantite' => $quantite,

                'places_disponibles' => $estPass
                    ? self::PLACES_PASS_AFFICHAGE
                    : (int) $row['places_disponibles'],
            ];
        }

        return [
            'id_commande' => $idCommande,
            'statut' => $commande['statut'],

            'montant_total' => round($montantCalcule, 2),
            'articles' => $articles,
        ];
    }

    public function synchroniserPanier(
        int $idUtilisateur,
        array $articles
    ): array {
        $this->pdo->beginTransaction();

        try {
            $stmtCommande = $this->pdo->prepare(
                "SELECT id_commande
                 FROM commande
                 WHERE id_utilisateur = :id_utilisateur
                   AND statut = 'en_cours'
                 ORDER BY id_commande DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmtCommande->execute([
                'id_utilisateur' => $idUtilisateur,
            ]);

            $commande = $stmtCommande->fetch();

            if ($commande === false) {
                $stmtInsertCmd = $this->pdo->prepare(
                    "INSERT INTO commande (
                        id_utilisateur,
                        montant_total,
                        statut
                    )
                    VALUES (
                        :id_utilisateur,
                        0.00,
                        'en_cours'
                    )"
                );

                $stmtInsertCmd->execute([
                    'id_utilisateur' => $idUtilisateur,
                ]);

                $idCommande = (int) $this->pdo->lastInsertId();
            } else {
                $idCommande = (int) $commande['id_commande'];
            }

            $stmtDelete = $this->pdo->prepare(
                'DELETE FROM panier
                 WHERE id_commande = :id_commande'
            );

            $stmtDelete->execute([
                'id_commande' => $idCommande,
            ]);

            $montantTotal = 0.0;

            $stmtSoiree = $this->pdo->prepare(
                'SELECT id_soiree, prix, places_disponibles
                 FROM soiree
                 WHERE id_soiree = :id_soiree'
            );

            $stmtInsertPanier = $this->pdo->prepare(
                'INSERT INTO panier (
                    id_commande,
                    id_soiree,
                    type_tarif,
                    quantite
                )
                VALUES (
                    :id_commande,
                    :id_soiree,
                    :type_tarif,
                    :quantite
                )'
            );

            foreach ($articles as $article) {
                $typeTarif = (string) $article['type_tarif'];
                $estPass = $this->estPass($typeTarif);

                $idSoiree = $estPass
                    ? self::ID_SOIREE_REFERENCE_PASS
                    : (int) $article['id_soiree'];

                $quantite = (int) $article['quantite'];

                if ($quantite < 1) {
                    throw new DomainException('quantite_invalide');
                }

                $stmtSoiree->execute([
                    'id_soiree' => $idSoiree,
                ]);

                $soiree = $stmtSoiree->fetch();

                if ($soiree === false) {
                    throw new DomainException('soiree_introuvable');
                }

                if (
                    !$estPass &&
                    $quantite > (int) $soiree['places_disponibles']
                ) {
                    throw new DomainException('places_insuffisantes');
                }

                $prixUnitaire = $this->calculerPrixUnitaire(
                    (float) $soiree['prix'],
                    $typeTarif
                );

                $montantTotal += $prixUnitaire * $quantite;

                $stmtInsertPanier->execute([
                    'id_commande' => $idCommande,
                    'id_soiree' => $idSoiree,
                    'type_tarif' => $typeTarif,
                    'quantite' => $quantite,
                ]);
            }

            $stmtUpdateCmd = $this->pdo->prepare(
                'UPDATE commande
                 SET montant_total = :montant_total,
                     `date` = CURRENT_TIMESTAMP
                 WHERE id_commande = :id_commande'
            );

            $stmtUpdateCmd->execute([
                'montant_total' => round($montantTotal, 2),
                'id_commande' => $idCommande,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }

        return $this->obtenirPanierEnCours($idUtilisateur);
    }

    private function obtenirPrixBasePass(): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT prix
             FROM soiree
             WHERE id_soiree = :id_soiree'
        );

        $stmt->execute([
            'id_soiree' => self::ID_SOIREE_REFERENCE_PASS,
        ]);

        $soiree = $stmt->fetch();

        if ($soiree === false) {
            throw new DomainException('soiree_introuvable');
        }

        return (float) $soiree['prix'];
    }

    private function estPass(string $typeTarif): bool
    {
        return in_array(
            $typeTarif,
            ['pass_3_jours', 'pass_3_jours_reduit'],
            true
        );
    }

    private function calculerPrixUnitaire(
        float $prixBase,
        string $typeTarif
    ): float {
        return match ($typeTarif) {
            'reduit' => round(
                $prixBase * self::TAUX_REDUIT,
                2
            ),

            'pass_3_jours' => round(
                $prixBase * self::MULTIPLICATEUR_PASS_3J,
                2
            ),

            'pass_3_jours_reduit' => round(
                $prixBase
                * self::MULTIPLICATEUR_PASS_3J
                * self::TAUX_REDUIT,
                2
            ),

            default => round($prixBase, 2),
        };
    }
}