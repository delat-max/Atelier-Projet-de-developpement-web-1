<?php

declare(strict_types=1);

use App\Auth;
use App\CatalogRepository;
use App\Middleware\RequireAuth;
use App\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app, CatalogRepository $catalog, UserRepository $users, Auth $auth): void {
    $json = static function (
        ResponseInterface $response,
        array $data,
        int $status = 200
    ): ResponseInterface {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    };

    $app->get('/api/dates', static function (ServerRequestInterface $request, ResponseInterface $response) use ($catalog, $json): ResponseInterface {
        return $json($response, ['data' => $catalog->findAvailableDates()]);
    });

    $app->get('/api/genres', static function (ServerRequestInterface $request, ResponseInterface $response) use ($catalog, $json): ResponseInterface {
        return $json($response, ['data' => $catalog->findAvailableGenres()]);
    });

    $app->get('/api/spectacles', static function (
        ServerRequestInterface $request,
        ResponseInterface $response
    ) use ($catalog, $json): ResponseInterface {
        $query = $request->getQueryParams();
        $date = isset($query['date']) && is_string($query['date']) ? trim($query['date']) : null;
        $genre = isset($query['genre']) && is_string($query['genre']) ? trim($query['genre']) : null;
        $tri = isset($query['tri']) && is_string($query['tri']) ? trim($query['tri']) : null;

        $page = isset($query['page']) ? max(1, (int)$query['page']) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $spectacles = $catalog->findAllSpectacles($date ?: null, $genre ?: null, $tri, $limit, $offset);
        $totalItems = $catalog->countAllSpectacles($date ?: null, $genre ?: null);
        $totalPages = (int) ceil($totalItems / $limit);

        return $json($response, [
            'data' => $spectacles,
            'meta' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit
            ]
        ]);
    });

    $app->get('/api/soirees/{id}', static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ) use ($catalog, $json): ResponseInterface {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            return $json($response, ['error' => 'Identifiant de soirée invalide'], 400);
        }

        $soiree = $catalog->findSoireeById($id);
        if ($soiree === null) {
            return $json($response, ['error' => 'Soirée introuvable'], 404);
        }

        $soiree['spectacles'] = $catalog->findSpectaclesBySoireeId($id);

        return $json($response, ['data' => $soiree]);
    });


    $app->post('/api/register', static function (ServerRequestInterface $request, ResponseInterface $response) use ($users, $auth, $json): ResponseInterface {
        $body = (array) ($request->getParsedBody() ?? []);
        $nom = trim((string) ($body['nom'] ?? ''));
        $prenom = trim((string) ($body['prenom'] ?? ''));
        $email = mb_strtolower(trim((string) ($body['email'] ?? '')));
        $password = (string) ($body['password'] ?? '');

        if ($nom === '' || $prenom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return $json($response, ['error' => 'Champs invalides (mot de passe : 8 caractères minimum)'], 422);
        }

        if ($auth->isAdminEmail($email) || $users->findByEmail($email) !== null) {
            return $json($response, ['error' => 'Cet email ne peut pas être utilisé'], 409);
        }

        $users->create($nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT));
        $user = $auth->attempt($email, $password);

        return $json($response, ['data' => $user], 201);
    });

    $app->post('/api/login', static function (ServerRequestInterface $request, ResponseInterface $response) use ($auth, $json): ResponseInterface {
        $body = (array) ($request->getParsedBody() ?? []);
        $user = $auth->attempt((string) ($body['email'] ?? ''), (string) ($body['password'] ?? ''));

        if ($user === null) {
            return $json($response, ['error' => 'Email ou mot de passe incorrect'], 401);
        }

        return $json($response, ['data' => $user]);
    });

    $app->post('/api/logout', static function (ServerRequestInterface $request, ResponseInterface $response) use ($auth, $json): ResponseInterface {
        $auth->logout();

        return $json($response, ['data' => ['logged_out' => true]]);
    });

    $app->get('/api/me', static function (ServerRequestInterface $request, ResponseInterface $response) use ($json): ResponseInterface {
        return $json($response, ['data' => $request->getAttribute('user')]);
    })->add(new RequireAuth($auth));


    // 401 si non connecté, 403 si non admin.
    $app->group('/api/admin', function (RouteCollectorProxy $group) use ($json): void {
        $group->get('/ping', static function (ServerRequestInterface $request, ResponseInterface $response) use ($json): ResponseInterface {
            return $json($response, ['data' => ['admin' => true, 'user' => $request->getAttribute('user')]]);
        });

    })->add(new RequireAuth($auth, true));
};