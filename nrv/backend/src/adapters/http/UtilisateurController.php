<?php

declare(strict_types=1);

namespace App\adapters\http;

use App\application\ports\api\AuthentifierUtilisateurInterface;
use App\application\ports\api\ChangerMotDePasseInterface;
use App\application\ports\api\InscrireUtilisateurInterface;
use App\application\ports\api\ModifierProfilInterface;
use App\application\ports\api\ObtenirProfilInterface;
use DomainException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UtilisateurController
{
    public function __construct(
        private InscrireUtilisateurInterface $inscrire,
        private AuthentifierUtilisateurInterface $authentifier,
        private ObtenirProfilInterface $obtenirProfil,
        private ModifierProfilInterface $modifierProfil,
        private ChangerMotDePasseInterface $changerMotDePasse
    ) {
    }

    public function inscrire(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $body = $this->body($request);

        foreach (['nom', 'prenom', 'email', 'password'] as $field) {
            if (!isset($body[$field]) || !is_string($body[$field])) {
                return $this->json(
                    $response,
                    ['error' => 'Informations d’inscription incomplètes'],
                    400
                );
            }
        }

        try {
            $id = $this->inscrire->execute(
                $body['nom'],
                $body['prenom'],
                $body['email'],
                $body['password']
            );
        } catch (DomainException $exception) {
            $message = match ($exception->getMessage()) {
                'email_deja_utilise' => ['Email déjà utilisé', 409],
                'email_invalide' => ['Adresse email invalide', 400],
                'mot_de_passe_invalide' => [
                    'Le mot de passe doit contenir au moins 10 caractères',
                    400
                ],
                default => ['Nom ou prénom invalide', 400]
            };

            return $this->json(
                $response,
                ['error' => $message[0]],
                $message[1]
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return $this->json(
                    $response,
                    ['error' => 'Email déjà utilisé'],
                    409
                );
            }

            throw $exception;
        }

        return $this->json(
            $response,
            ['data' => ['id_utilisateur' => $id]],
            201
        );
    }

    public function connexion(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $body = $this->body($request);

        if (
            !isset($body['email'], $body['password']) ||
            !is_string($body['email']) ||
            !is_string($body['password'])
        ) {
            return $this->json(
                $response,
                ['error' => 'Email et mot de passe requis'],
                400
            );
        }

        $utilisateur = $this->authentifier->execute(
            $body['email'],
            $body['password']
        );

        if ($utilisateur === null) {
            return $this->json(
                $response,
                ['error' => 'Email ou mot de passe incorrect'],
                401
            );
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $utilisateur['id_utilisateur'];

        return $this->json(
            $response,
            ['data' => $utilisateur]
        );
    }

    public function deconnexion(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]);
        }

        session_destroy();

        return $this->json(
            $response,
            ['data' => ['message' => 'Déconnexion réussie']]
        );
    }

    public function profil(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $id = $this->userId();

        if ($id === null) {
            return $this->json(
                $response,
                ['error' => 'Authentification requise'],
                401
            );
        }

        $utilisateur = $this->obtenirProfil->execute($id);

        if ($utilisateur === null) {
            return $this->json(
                $response,
                ['error' => 'Utilisateur introuvable'],
                404
            );
        }

        return $this->json(
            $response,
            ['data' => $utilisateur]
        );
    }

    public function modifierProfil(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $id = $this->userId();

        if ($id === null) {
            return $this->json(
                $response,
                ['error' => 'Authentification requise'],
                401
            );
        }

        $body = $this->body($request);

        foreach (['nom', 'prenom', 'email'] as $field) {
            if (!isset($body[$field]) || !is_string($body[$field])) {
                return $this->json(
                    $response,
                    ['error' => 'Nom, prénom et email requis'],
                    400
                );
            }
        }

        try {
            $this->modifierProfil->execute(
                $id,
                $body['nom'],
                $body['prenom'],
                $body['email']
            );
        } catch (DomainException $exception) {
            $message = $exception->getMessage() === 'email_invalide'
                ? 'Adresse email invalide'
                : 'Nom ou prénom invalide';

            return $this->json(
                $response,
                ['error' => $message],
                400
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return $this->json(
                    $response,
                    ['error' => 'Email déjà utilisé par un autre compte'],
                    409
                );
            }

            throw $exception;
        }

        return $this->profil($request, $response);
    }

    public function modifierMotDePasse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $id = $this->userId();

        if ($id === null) {
            return $this->json(
                $response,
                ['error' => 'Authentification requise'],
                401
            );
        }

        $body = $this->body($request);

        if (
            !isset($body['current_password'], $body['new_password']) ||
            !is_string($body['current_password']) ||
            !is_string($body['new_password'])
        ) {
            return $this->json(
                $response,
                ['error' => 'Mot de passe actuel et nouveau mot de passe requis'],
                400
            );
        }

        try {
            $changed = $this->changerMotDePasse->execute(
                $id,
                $body['current_password'],
                $body['new_password']
            );
        } catch (DomainException) {
            return $this->json(
                $response,
                ['error' => 'Le nouveau mot de passe doit contenir au moins 10 caractères'],
                400
            );
        }

        if (!$changed) {
            return $this->json(
                $response,
                ['error' => 'Mot de passe actuel incorrect'],
                400
            );
        }

        session_regenerate_id(true);

        return $this->json(
            $response,
            ['data' => ['message' => 'Mot de passe modifié']]
        );
    }

    private function userId(): ?int
    {
        return isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])
            ? $_SESSION['user_id']
            : null;
    }

    private function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
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
