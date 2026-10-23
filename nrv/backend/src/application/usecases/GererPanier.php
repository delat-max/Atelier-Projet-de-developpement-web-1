<?php

declare(strict_types=1);

namespace App\application\usecases;

use App\application\ports\api\GererPanierInterface;
use App\application\ports\spi\PanierRepositoryInterface;
use DomainException;

final class GererPanier implements GererPanierInterface
{
    public function __construct(
        private PanierRepositoryInterface $panierRepository
    ) {
    }

    public function obtenir(int $idUtilisateur): array
    {
        return $this->panierRepository->obtenirPanierEnCours($idUtilisateur);
    }

    public function synchroniser(int $idUtilisateur, array $articles): array
    {
        $articlesValides = [];
        foreach ($articles as $article) {
            if (!is_array($article)) {
                throw new DomainException('article_invalide');
            }

            $idSoiree = filter_var($article['id_soiree'] ?? null, FILTER_VALIDATE_INT);
            $quantite = filter_var($article['quantite'] ?? null, FILTER_VALIDATE_INT);
            $typeTarif = $article['type_tarif'] ?? 'normal';

            if (
                $idSoiree === false || $idSoiree < 1 ||
                $quantite === false || $quantite < 1 ||
                !in_array($typeTarif, ['normal', 'reduit', 'pass_3_jours'], true)
            ) {
                throw new DomainException('article_invalide');
            }

            $cle = $idSoiree . '_' . $typeTarif;
            if (isset($articlesValides[$cle])) {
                $articlesValides[$cle]['quantite'] += $quantite;
            } else {
                $articlesValides[$cle] = [
                    'id_soiree' => $idSoiree,
                    'type_tarif' => $typeTarif,
                    'quantite' => $quantite,
                ];
            }
        }

        return $this->panierRepository->synchroniserPanier(
            $idUtilisateur,
            array_values($articlesValides)
        );
    }
}