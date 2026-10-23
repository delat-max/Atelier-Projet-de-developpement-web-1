<?php

declare(strict_types=1);

namespace App\adapters\persistence;

use App\application\ports\spi\PanierRepositoryInterface;
use DomainException;
use PDO;

final class MySQLPanierRepository implements PanierRepositoryInterface
{
    private const TAUX_REDUIT = 0.80; // -20% sur tarif normal
    private const MULTIPLICATEUR_PASS_3J = 3 * 0.97; // x3 et 3% de remise (normalement)

    public function __construct(private PDO $pdo)
    {
    }

    public function obtenirPanierEnCours(int $idUtilisateur): array
    {
        $stmtCommande = $this->pdo->prepare(
            "SELECT id_commande, montant_total, statut
             FROM commande
             WHERE id_utilisateur = :id_utilisateur AND statut = 'en_cours'
             ORDER BY id_commande DESC
             LIMIT 1"
        );
        $stmtCommande->execute(['id_utilisateur' => $idUtilisateur]);
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
                    s.nom, s.`date`, s.lieu, s.prix AS prix_base, s.places_disponibles
             FROM panier p
             INNER JOIN soiree s ON s.id_soiree = p.id_soiree
             WHERE p.id_commande = :id_commande
             ORDER BY p.id_panier ASC'
        );
        $stmtPanier->execute(['id_commande' => $idCommande]);

        $articles = [];
        while ($row = $stmtPanier->fetch()) {
            $prixBase = (float) $row['prix_base'];
            $typeTarif = $row['type_tarif'];
            $prixUnitaire = $this->calculerPrixUnitaire($prixBase, $typeTarif);

            $articles[] = [
                'id_panier' => (int) $row['id_panier'],
                'id_soiree' => (int) $row['id_soiree'],
                'nom' => in_array(
                    $row['type_tarif'],
                    ['pass_3_jours', 'pass_3_jours_reduit'],
                    true
                )
                    ? 'Pass 3 Jours — ' . $row['nom']
                    : $row['nom'],
                'date' => $row['date'],
                'lieu' => $row['lieu'],
                'type_tarif' => $typeTarif,
                'prix_base' => $prixBase,
                'prix' => $prixUnitaire,
                'quantite' => (int) $row['quantite'],
                'places_disponibles' => (int) $row['places_disponibles'],
            ];
        }

        return [
            'id_commande' => $idCommande,
            'statut' => $commande['statut'],
            'montant_total' => (float) $commande['montant_total'],
            'articles' => $articles,
        ];
    }

    public function synchroniserPanier(int $idUtilisateur, array $articles): array
    {
        $this->pdo->beginTransaction();

        try {
            $stmtCommande = $this->pdo->prepare(
                "SELECT id_commande
                 FROM commande
                 WHERE id_utilisateur = :id_utilisateur AND statut = 'en_cours'
                 ORDER BY id_commande DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtCommande->execute(['id_utilisateur' => $idUtilisateur]);
            $commande = $stmtCommande->fetch();

            if ($commande === false) {
                $stmtInsertCmd = $this->pdo->prepare(
                    "INSERT INTO commande (id_utilisateur, montant_total, statut)
                     VALUES (:id_utilisateur, 0.00, 'en_cours')"
                );
                $stmtInsertCmd->execute(['id_utilisateur' => $idUtilisateur]);
                $idCommande = (int) $this->pdo->lastInsertId();
            } else {
                $idCommande = (int) $commande['id_commande'];
            }

            $stmtDelete = $this->pdo->prepare(
                'DELETE FROM panier WHERE id_commande = :id_commande'
            );
            $stmtDelete->execute(['id_commande' => $idCommande]);

            $montantTotal = 0.0;

            if (!empty($articles)) {
                $stmtSoiree = $this->pdo->prepare(
                    'SELECT id_soiree, prix, places_disponibles
                     FROM soiree
                     WHERE id_soiree = :id_soiree'
                );

                $stmtInsertPanier = $this->pdo->prepare(
                    'INSERT INTO panier (id_commande, id_soiree, type_tarif, quantite)
                     VALUES (:id_commande, :id_soiree, :type_tarif, :quantite)'
                );

                foreach ($articles as $article) {
                    $stmtSoiree->execute(['id_soiree' => $article['id_soiree']]);
                    $soiree = $stmtSoiree->fetch();

                    if ($soiree === false) {
                        throw new DomainException('soiree_introuvable');
                    }

                    if ($article['quantite'] > (int) $soiree['places_disponibles']) {
                        throw new DomainException('places_insuffisantes');
                    }

                    $prixUnitaire = $this->calculerPrixUnitaire(
                        (float) $soiree['prix'],
                        $article['type_tarif']
                    );

                    $montantTotal += round($prixUnitaire * $article['quantite'], 2);

                    $stmtInsertPanier->execute([
                        'id_commande' => $idCommande,
                        'id_soiree' => $article['id_soiree'],
                        'type_tarif' => $article['type_tarif'],
                        'quantite' => $article['quantite'],
                    ]);
                }
            }

            $stmtUpdateCmd = $this->pdo->prepare(
                'UPDATE commande
                 SET montant_total = :montant_total, `date` = CURRENT_TIMESTAMP
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

    private function calculerPrixUnitaire(float $prixBase, string $typeTarif): float {
        return match ($typeTarif) {
            'reduit' => round($prixBase * self::TAUX_REDUIT, 2),

            'pass_3_jours' => round($prixBase * self::MULTIPLICATEUR_PASS_3J, 2),

            'pass_3_jours_reduit' => round($prixBase * self::MULTIPLICATEUR_PASS_3J * self::TAUX_REDUIT, 2),

            default => round($prixBase, 2),
        };
    }
}