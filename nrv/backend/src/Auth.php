<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public function __construct(private array $config, private UserRepository $users)
    {
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name($this->config['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function isAdminEmail(string $email): bool
    {
        return in_array(mb_strtolower(trim($email)), $this->config['admin_emails'], true);
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));


        $hash = $user['password'] ?? '$2y$10$usesomesillystringforsaltcuUpXz3h1Z5d6C9m0Q0v6y9n8dX8e';
        if (!password_verify($password, $hash) || $user === null) {
            return null;
        }

        $this->start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id_utilisateur'];

        return $this->publicUser($user);
    }

    public function user(): ?array
    {
        $this->start();
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $statement = $this->usersPdoLookup((int) $_SESSION['user_id']);
        return $statement;
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && $user['is_admin'];
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function publicUser(array $user): array
    {
        return [
            'id_utilisateur' => (int) $user['id_utilisateur'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'is_admin' => $this->isAdminEmail($user['email']),
        ];
    }

    private function usersPdoLookup(int $id): ?array
    {
        $user = $this->users->findById($id);
        return $user === null ? null : $this->publicUser($user);
    }
}