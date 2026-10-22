<?php

declare(strict_types=1);

namespace App\adapters\persistence;

use App\application\ports\spi\UtilisateurRepositoryInterface;
use PDO;

final class MySQLUtilisateurRepository implements UtilisateurRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(
        string $nom,
        string $prenom,
        string $email,
        string $passwordHash
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password)
             VALUES (:nom, :prenom, :email, :password)'
        );

        $statement->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $passwordHash
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email, password
             FROM utilisateur
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute(['email' => $email]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email, password
             FROM utilisateur
             WHERE id_utilisateur = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    public function updateProfile(
        int $id,
        string $nom,
        string $prenom,
        string $email
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE utilisateur
             SET nom = :nom, prenom = :prenom, email = :email
             WHERE id_utilisateur = :id'
        );

        $statement->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'id' => $id
        ]);
    }

    public function updatePassword(
        int $id,
        string $passwordHash
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE utilisateur
             SET password = :password
             WHERE id_utilisateur = :id'
        );

        $statement->execute([
            'password' => $passwordHash,
            'id' => $id
        ]);
    }
}
