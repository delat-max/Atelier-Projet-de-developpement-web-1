<?php

declare(strict_types=1);

namespace App\adapters\http;

use DomainException;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class AdminController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function me(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $this->json($response, [
            'data' => $request->getAttribute('admin_user'),
        ]);
    }

    public function statistiques(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $stmt = $this->pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM soiree) AS nombre_soirees,
                (SELECT COUNT(*) FROM spectacle) AS nombre_spectacles,
                (SELECT COUNT(*)
                 FROM billet b
                 INNER JOIN commande c ON c.id_commande = b.id_commande
                 WHERE c.statut = 'payee') AS billets_vendus,
                (SELECT COUNT(*)
                 FROM commande WHERE statut = 'payee') AS commandes_payees,
                (SELECT COALESCE(SUM(montant_total), 0)
                 FROM commande WHERE statut = 'payee') AS revenu_total"
        );

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        foreach ([
            'nombre_soirees',
            'nombre_spectacles',
            'billets_vendus',
            'commandes_payees',
        ] as $key) {
            $stats[$key] = (int) $stats[$key];
        }

        $stats['revenu_total'] = (float) $stats['revenu_total'];

        return $this->json($response, ['data' => $stats]);
    }

    public function soirees(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $sql = "SELECT
                    s.id_soiree,
                    s.nom,
                    s.thematique,
                    s.`date`,
                    s.horaire_debut,
                    s.lieu,
                    s.places_disponibles,
                    s.prix,
                    (SELECT COUNT(*)
                     FROM spectacle sp
                     WHERE sp.id_soiree = s.id_soiree)
                        AS nombre_spectacles,
                    (SELECT COUNT(*)
                     FROM billet b
                     INNER JOIN commande c ON c.id_commande = b.id_commande
                     WHERE b.id_soiree = s.id_soiree
                       AND c.statut = 'payee')
                        AS billets_vendus,
                    (SELECT COUNT(*)
                     FROM billet b
                     INNER JOIN commande c ON c.id_commande = b.id_commande
                     WHERE b.id_soiree = s.id_soiree
                       AND c.statut = 'payee'
                       AND b.categorie_tarif IN (
                           'pass_3_jours', 'pass_3_jours_reduit'
                       ))
                        AS pass_vendus,
                    (SELECT COALESCE(SUM(
                        CASE b.categorie_tarif
                            WHEN 'normal' THEN s.prix
                            WHEN 'reduit' THEN s.prix * 0.80
                            ELSE 0
                        END
                     ), 0)
                     FROM billet b
                     INNER JOIN commande c ON c.id_commande = b.id_commande
                     WHERE b.id_soiree = s.id_soiree
                       AND c.statut = 'payee')
                        AS revenu_individuel
                FROM soiree s
                ORDER BY s.`date`, s.horaire_debut, s.nom";

        $soirees = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($soirees as &$soiree) {
            foreach ([
                'id_soiree',
                'places_disponibles',
                'nombre_spectacles',
                'billets_vendus',
                'pass_vendus',
            ] as $key) {
                $soiree[$key] = (int) $soiree[$key];
            }

            $soiree['prix'] = (float) $soiree['prix'];
            $soiree['revenu_individuel'] =
                (float) $soiree['revenu_individuel'];
        }
        unset($soiree);

        return $this->json($response, ['data' => $soirees]);
    }

    public function artistes(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $artistes = $this->pdo->query(
            'SELECT id_artiste, nom, information, genre
             FROM artiste
             ORDER BY nom'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($artistes as &$artiste) {
            $artiste['id_artiste'] = (int) $artiste['id_artiste'];
        }
        unset($artiste);

        return $this->json($response, ['data' => $artistes]);
    }

    public function creerSoiree(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json(
                $response,
                ['error' => 'Données invalides'],
                400
            );
        }

        $nom = trim((string) ($body['nom'] ?? ''));
        $thematique = trim((string) ($body['thematique'] ?? ''));
        $date = (string) ($body['date'] ?? '');
        $heure = (string) ($body['horaire_debut'] ?? '');
        $lieu = trim((string) ($body['lieu'] ?? ''));
        $places = filter_var(
            $body['places_disponibles'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $prix = filter_var(
            $body['prix'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $dateParsee = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $heureValide = preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
            $heure
        ) === 1;

        if (
            $nom === '' || strlen($nom) > 100 ||
            $lieu === '' || strlen($lieu) > 100 ||
            strlen($thematique) > 100
        ) {
            return $this->json(
                $response,
                ['error' => 'Nom, lieu ou thématique invalide'],
                400
            );
        }

        if (
            $dateParsee === false ||
            $dateParsee->format('Y-m-d') !== $date ||
            !$heureValide
        ) {
            return $this->json(
                $response,
                ['error' => 'Date ou heure invalide'],
                400
            );
        }

        if ($places === false || $prix === false || $prix < 0) {
            return $this->json(
                $response,
                ['error' => 'Capacité ou prix invalide'],
                400
            );
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO soiree (
                nom, thematique, `date`, horaire_debut,
                lieu, places_disponibles, prix
            ) VALUES (
                :nom, :thematique, :date, :heure,
                :lieu, :places, :prix
            )'
        );

        $stmt->execute([
            'nom' => $nom,
            'thematique' => $thematique !== '' ? $thematique : null,
            'date' => $date,
            'heure' => $heure,
            'lieu' => $lieu,
            'places' => $places,
            'prix' => $prix,
        ]);

        return $this->json(
            $response,
            ['data' => ['id_soiree' => (int) $this->pdo->lastInsertId()]],
            201
        );
    }

    public function ajouterSpectacle(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json(
                $response,
                ['error' => 'Données invalides'],
                400
            );
        }

        $idSoiree = filter_var(
            $body['id_soiree'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $idArtiste = filter_var(
            $body['id_artiste'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        $creneau = $body['creneau'] ?? '';

        if (
            $idSoiree === false ||
            $idArtiste === false ||
            !is_string($creneau) ||
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
                $creneau
            ) !== 1
        ) {
            return $this->json(
                $response,
                ['error' => 'Soirée, artiste ou créneau invalide'],
                400
            );
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'SELECT id_soiree FROM soiree
                 WHERE id_soiree = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $idSoiree]);

            if ($stmt->fetchColumn() === false) {
                throw new DomainException('soiree_introuvable');
            }

            $stmt = $this->pdo->prepare(
                'SELECT id_artiste, nom, information, genre
                 FROM artiste WHERE id_artiste = :id'
            );
            $stmt->execute(['id' => $idArtiste]);
            $artiste = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($artiste === false) {
                throw new DomainException('artiste_introuvable');
            }

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM spectacle WHERE id_soiree = :id'
            );
            $stmt->execute(['id' => $idSoiree]);

            if ((int) $stmt->fetchColumn() >= 3) {
                throw new DomainException('limite_atteinte');
            }

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM spectacle sp
                 INNER JOIN spectacle_artiste sa
                    ON sa.id_spectacle = sp.id_spectacle
                 WHERE sp.id_soiree = :soiree
                   AND sa.id_artiste = :artiste'
            );
            $stmt->execute([
                'soiree' => $idSoiree,
                'artiste' => $idArtiste,
            ]);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new DomainException('artiste_deja_programme');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO spectacle (
                    id_soiree, titre, description, genre,
                    image, video, creneau
                ) VALUES (
                    :soiree, :titre, :description, :genre,
                    NULL, NULL, :creneau
                )'
            );

            $stmt->execute([
                'soiree' => $idSoiree,
                'titre' => $artiste['nom'],
                'description' => $artiste['information'],
                'genre' => $artiste['genre'] ?: 'Rock',
                'creneau' => $creneau,
            ]);

            $idSpectacle = (int) $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                'INSERT INTO spectacle_artiste (id_spectacle, id_artiste)
                 VALUES (:spectacle, :artiste)'
            );
            $stmt->execute([
                'spectacle' => $idSpectacle,
                'artiste' => $idArtiste,
            ]);

            $this->pdo->commit();

            return $this->json(
                $response,
                ['data' => ['id_spectacle' => $idSpectacle]],
                201
            );
        } catch (DomainException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $messages = [
                'soiree_introuvable' => 'Soirée introuvable',
                'artiste_introuvable' => 'Artiste introuvable',
                'limite_atteinte' =>
                    'Cette soirée contient déjà trois spectacles',
                'artiste_deja_programme' =>
                    'Cet artiste est déjà programmé pour cette soirée',
            ];

            $status = in_array(
                $exception->getMessage(),
                ['limite_atteinte', 'artiste_deja_programme'],
                true
            ) ? 409 : 400;

            return $this->json(
                $response,
                ['error' => $messages[$exception->getMessage()]
                    ?? 'Impossible d’ajouter ce spectacle'],
                $status
            );
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function json(
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write(
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            )
        );

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}