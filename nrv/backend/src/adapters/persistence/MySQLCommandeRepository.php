<?php

declare(strict_types=1);

namespace App\adapters\persistence;

use App\application\ports\spi\CommandeRepositoryInterface;
use DomainException;
use PDO;
use Throwable;

final class MySQLCommandeRepository implements CommandeRepositoryInterface {
    private const TAUX_REDUIT = 0.80;

    public function __construct(private PDO $pdo) {}

    public function preparerPaiement(
        int $idUtilisateur,
        int $idCommande
    ): array {
        $stmt = $this->pdo->prepare(
            "SELECT id_commande, montant_total, statut
             FROM commande
             WHERE id_commande = :id_commande
               AND id_utilisateur = :id_utilisateur"
        );

        $stmt->execute([
            'id_commande' => $idCommande,
            'id_utilisateur' => $idUtilisateur,
        ]);

        $commande = $stmt->fetch();

        if ($commande === false) {
            throw new DomainException('commande_introuvable');
        }

        if ($commande['statut'] !== 'en_cours') {
            throw new DomainException('commande_non_payable');
        }

        $stmtArticles = $this->pdo->prepare(
            "SELECT p.id_soiree, p.type_tarif, p.quantite,
                    s.nom, s.prix
             FROM panier p
             INNER JOIN soiree s ON s.id_soiree = p.id_soiree
             WHERE p.id_commande = :id_commande
             ORDER BY p.id_panier"
        );

        $stmtArticles->execute(['id_commande' => $idCommande]);
        $articles = $stmtArticles->fetchAll();

        if ($articles === []) {
            throw new DomainException('panier_vide');
        }

        $montant = 0.0;

        foreach ($articles as $article) {
            $typeTarif = $article['type_tarif'];

            if (in_array(
                $typeTarif,
                ['pass_3_jours', 'pass_3_jours_reduit'],
                true
            )) {
                throw new DomainException(
                    'pass_3_jours_schema_incompatible'
                );
            }

            if (!in_array($typeTarif, ['normal', 'reduit'], true)) {
                throw new DomainException('tarif_invalide');
            }

            $prix = $this->calculerPrix(
                (float) $article['prix'],
                $typeTarif
            );

            $montant += $prix * (int) $article['quantite'];
        }

        return [
            'id_commande' => $idCommande,
            'montant_total' => round($montant, 2),
            'articles' => $articles,
        ];
    }

    public function finaliserCommandeEtGenererBillets(
        int $idUtilisateur,
        int $idCommande,
        float $montantConfirme
    ): array {
        $this->pdo->beginTransaction();

        try {
            $stmtCommande = $this->pdo->prepare(
                "SELECT id_commande, statut
                 FROM commande
                 WHERE id_commande = :id_commande
                   AND id_utilisateur = :id_utilisateur
                 FOR UPDATE"
            );

            $stmtCommande->execute([
                'id_commande' => $idCommande,
                'id_utilisateur' => $idUtilisateur,
            ]);

            $commande = $stmtCommande->fetch();

            if ($commande === false) {
                throw new DomainException('commande_introuvable');
            }

            if ($commande['statut'] !== 'en_cours') {
                throw new DomainException('commande_non_payable');
            }

            $stmtPanier = $this->pdo->prepare(
                "SELECT id_soiree, type_tarif, quantite
                 FROM panier
                 WHERE id_commande = :id_commande
                 ORDER BY id_panier
                 FOR UPDATE"
            );

            $stmtPanier->execute(['id_commande' => $idCommande]);
            $articles = $stmtPanier->fetchAll();

            if ($articles === []) {
                throw new DomainException('panier_vide');
            }

            $montantCalcule = 0.0;
            $quantitesParSoiree = [];
            $articlesValides = [];

            $stmtSoiree = $this->pdo->prepare(
                "SELECT id_soiree, nom, prix, places_disponibles
                 FROM soiree
                 WHERE id_soiree = :id_soiree
                 FOR UPDATE"
            );

            foreach ($articles as $article) {
                $typeTarif = $article['type_tarif'];
                $idSoiree = (int) $article['id_soiree'];
                $quantite = (int) $article['quantite'];

                if ($quantite < 1) {
                    throw new DomainException('quantite_invalide');
                }

                if (in_array(
                    $typeTarif,
                    ['pass_3_jours', 'pass_3_jours_reduit'],
                    true
                )) {
                    throw new DomainException(
                        'pass_3_jours_schema_incompatible'
                    );
                }

                if (!in_array($typeTarif, ['normal', 'reduit'], true)) {
                    throw new DomainException('tarif_invalide');
                }

                $stmtSoiree->execute(['id_soiree' => $idSoiree]);
                $soiree = $stmtSoiree->fetch();

                if ($soiree === false) {
                    throw new DomainException('soiree_introuvable');
                }

                $prixUnitaire = $this->calculerPrix(
                    (float) $soiree['prix'],
                    $typeTarif
                );

                $montantCalcule += $prixUnitaire * $quantite;

                $quantitesParSoiree[$idSoiree] =
                    ($quantitesParSoiree[$idSoiree] ?? 0) + $quantite;

                $articlesValides[] = [
                    'id_soiree' => $idSoiree,
                    'nom_soiree' => $soiree['nom'],
                    'type_tarif' => $typeTarif,
                    'quantite' => $quantite,
                ];
            }

            $montantCalcule = round($montantCalcule, 2);

            if (abs($montantCalcule - $montantConfirme) > 0.009) {
                throw new DomainException('montant_modifie');
            }

            foreach ($quantitesParSoiree as $idSoiree => $quantite) {
                $stmtSoiree->execute(['id_soiree' => $idSoiree]);
                $soiree = $stmtSoiree->fetch();

                if (
                    $soiree === false ||
                    (int) $soiree['places_disponibles'] < $quantite
                ) {
                    throw new DomainException('places_insuffisantes');
                }
            }

            $stmtStock = $this->pdo->prepare(
                "UPDATE soiree
                 SET places_disponibles = places_disponibles - :quantite
                 WHERE id_soiree = :id_soiree
                   AND places_disponibles >= :quantite"
            );

            foreach ($quantitesParSoiree as $idSoiree => $quantite) {
                $stmtStock->execute([
                    'quantite' => $quantite,
                    'id_soiree' => $idSoiree,
                ]);

                if ($stmtStock->rowCount() !== 1) {
                    throw new DomainException('places_insuffisantes');
                }
            }

            $stmtBillet = $this->pdo->prepare(
                "INSERT INTO billet (
                    id_commande,
                    id_soiree,
                    reference,
                    token,
                    categorie_tarif
                ) VALUES (
                    :id_commande,
                    :id_soiree,
                    :reference,
                    :token,
                    :categorie_tarif
                )"
            );

            $billets = [];

            foreach ($articlesValides as $article) {
                for ($i = 0; $i < $article['quantite']; $i++) {
                    $reference = 'NRV-' . strtoupper(
                        bin2hex(random_bytes(8))
                    );

                    $token = bin2hex(random_bytes(32));

                    $stmtBillet->execute([
                        'id_commande' => $idCommande,
                        'id_soiree' => $article['id_soiree'],
                        'reference' => $reference,
                        'token' => $token,
                        'categorie_tarif' => $article['type_tarif'],
                    ]);

                    $billets[] = [
                        'id_billet' => (int) $this->pdo->lastInsertId(),
                        'reference' => $reference,
                        'token' => $token,
                        'id_soiree' => $article['id_soiree'],
                        'nom_soiree' => $article['nom_soiree'],
                        'categorie_tarif' => $article['type_tarif'],
                    ];
                }
            }

            $stmtUpdate = $this->pdo->prepare(
                "UPDATE commande
                 SET statut = 'payee',
                     montant_total = :montant_total
                 WHERE id_commande = :id_commande
                   AND statut = 'en_cours'"
            );

            $stmtUpdate->execute([
                'montant_total' => $montantCalcule,
                'id_commande' => $idCommande,
            ]);

            if ($stmtUpdate->rowCount() !== 1) {
                throw new DomainException('commande_non_payable');
            }

            $this->pdo->commit();

            return [
                'id_commande' => $idCommande,
                'statut' => 'payee',
                'montant_total' => $montantCalcule,
                'billets' => $billets,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function calculerPrix(
        float $prixBase,
        string $typeTarif
    ): float {
        return match ($typeTarif) {
            'reduit' => round($prixBase * self::TAUX_REDUIT, 2),
            default => round($prixBase, 2),
        };
    }
}
