<?php

declare(strict_types=1);

namespace App;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email, password
             FROM utilisateur WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_utilisateur, nom, prenom, email, password
             FROM utilisateur WHERE id_utilisateur = :id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $nom, string $prenom, string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password)
             VALUES (:nom, :prenom, :email, :password)'
        );
        $statement->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $passwordHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}