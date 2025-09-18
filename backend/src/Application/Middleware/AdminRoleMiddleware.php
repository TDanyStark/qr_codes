<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpForbiddenException;

/**
 * Middleware to enforce that the authenticated user has the 'admin' role.
 * Assumes a previous middleware (JwtAuthMiddleware) decoded the token and
 * attached the decoded payload to the request attribute 'jwt'.
 */
class AdminRoleMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $jwt = $request->getAttribute('jwt');

        // If jwt isn't present, treat as forbidden (JwtAuthMiddleware should normally run before this)
        if (!$jwt || !is_array($jwt) && !is_object($jwt)) {
            throw new HttpForbiddenException($request, 'Access denied');
        }

        $rol = null;
        if (is_array($jwt) && isset($jwt['rol'])) {
            $rol = $jwt['rol'];
        } elseif (is_object($jwt) && isset($jwt->rol)) {
            $rol = $jwt->rol;
        }

        if ($rol !== 'admin') {
            throw new HttpForbiddenException($request, 'Admin role required');
        }

        return $handler->handle($request);
    }
}
