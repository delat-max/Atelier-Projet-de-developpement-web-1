<?php

declare(strict_types=1);

namespace App;

use PDO;

final class CatalogRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findAllSpectacles(?string $date = null, ?string $genre = null, ?string $tri = null, ?int $limit = null, ?int $offset = null): array
    {
        $spectacles = $this->findSpectacles(null, $date, $genre, $tri);

        if ($limit !== null && $offset !== null) {
            return array_slice($spectacles, $offset, $limit);
        }

        return $spectacles;
    }

    public function countAllSpectacles(?string $date = null, ?string $genre = null): int
    {
        $spectacles = $this->findSpectacles(null, $date, $genre);
        return count($spectacles);
    }

    public function findAvailableDates(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT `date` FROM soiree WHERE `date` IS NOT NULL ORDER BY `date`');
        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function findAvailableGenres(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT genre FROM spectacle WHERE genre IS NOT NULL AND TRIM(genre) != \'\' ORDER BY genre');
        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function findSoireeById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_soiree, nom, thematique, `date`, horaire_debut, lieu,
                    places_disponibles, prix
             FROM soiree
             WHERE id_soiree = :id'
        );
        $statement->execute(['id' => $id]);
        $soiree = $statement->fetch();

        if ($soiree === false) {
            return null;
        }

        $soiree['id_soiree'] = (int) $soiree['id_soiree'];
        $soiree['places_disponibles'] = (int) $soiree['places_disponibles'];
        $soiree['prix'] = (string) $soiree['prix'];

        return $soiree;
    }

    public function findSpectaclesBySoireeId(int $id): array
    {
        return $this->findSpectacles($id);
    }

    private function findSpectacles(?int $idSoiree = null, ?string $date = null, ?string $genre = null, ?string $tri = null): array
    {
        $sql = 'SELECT spectacle.id_spectacle, spectacle.id_soiree, spectacle.titre,
                       spectacle.description, spectacle.genre, spectacle.image, spectacle.video,
                       spectacle.creneau AS horaire, soiree.nom AS soiree_nom,
                       soiree.`date` AS date_soiree, soiree.lieu,
                       artiste.id_artiste, artiste.nom AS artiste_nom
                FROM spectacle
                INNER JOIN soiree ON soiree.id_soiree = spectacle.id_soiree
                LEFT JOIN spectacle_artiste
                    ON spectacle_artiste.id_spectacle = spectacle.id_spectacle
                LEFT JOIN artiste ON artiste.id_artiste = spectacle_artiste.id_artiste';

        $conditions = [];
        $parameters = [];

        if ($idSoiree !== null) {
            $conditions[] = 'spectacle.id_soiree = :id_soiree';
            $parameters['id_soiree'] = $idSoiree;
        }

        if ($date !== null && $date !== '') {
            $conditions[] = 'soiree.`date` = :date';
            $parameters['date'] = $date;
        }

        if ($genre !== null && $genre !== '') {
            $conditions[] = 'LOWER(TRIM(spectacle.genre)) = LOWER(TRIM(:genre))';
            $parameters['genre'] = $genre;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $ordre = match ($tri) {
            'date-decroissante' => 'soiree.`date` DESC, spectacle.creneau DESC, spectacle.titre, artiste.nom',
            'titre' => 'spectacle.titre, soiree.`date`, spectacle.creneau, artiste.nom',
            default => 'soiree.`date`, spectacle.creneau, spectacle.titre, artiste.nom',
        };
        $sql .= ' ORDER BY ' . $ordre;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        $spectacles = [];
        while ($row = $statement->fetch()) {
            $id = (int) $row['id_spectacle'];
            if (!isset($spectacles[$id])) {
                $spectacles[$id] = [
                    'id_spectacle' => $id,
                    'id_soiree' => (int) $row['id_soiree'],
                    'titre' => $row['titre'],
                    'description' => $row['description'],
                    'genre' => $row['genre'],
                    'image' => $row['image'],
                    'video' => $row['video'],
                    'horaire' => $row['horaire'],
                    'date' => $row['date_soiree'],
                    'soiree_nom' => $row['soiree_nom'],
                    'lieu' => $row['lieu'],
                    'artistes' => [],
                ];
            }

            if ($row['id_artiste'] !== null) {
                $spectacles[$id]['artistes'][] = [
                    'id_artiste' => (int) $row['id_artiste'],
                    'nom' => $row['artiste_nom'],
                ];
            }
        }

        return array_values($spectacles);
    }
}