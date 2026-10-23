<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
* - $adminOnly = false : il faut être connecté (401 sinon)
* - $adminOnly = true  : il faut être admin (401 si non connecté, 403 si connecté non admin)
*/
final class RequireAuth implements MiddlewareInterface
{
    public function __construct(private Auth $auth, private bool $adminOnly = false)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->error('Authentification requise', 401);
        }
        if ($this->adminOnly && !$user['is_admin']) {
            return $this->error('Accès réservé aux administrateurs', 403);
        }

        return $handler->handle($request->withAttribute('user', $user));
    }

    private function error(string $message, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}