<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Security\JwtService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;

class JwtAuthMiddleware implements Middleware
{
    private JwtService $jwtService;
    private LoggerInterface $logger;

    public function __construct(JwtService $jwtService, LoggerInterface $logger)
    {
        $this->jwtService = $jwtService;
        $this->logger = $logger;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');
        if (!$auth || stripos($auth, 'Bearer ') !== 0) {
            throw new HttpUnauthorizedException($request, 'Missing or invalid Authorization header');
        }

        $token = trim(substr($auth, 7));

        try {
            $decoded = $this->jwtService->decode($token);
            $request = $request->withAttribute('jwt', $decoded);
        } catch (\Throwable $e) {
            throw new HttpUnauthorizedException($request, 'Invalid or expired token');
        }

        return $handler->handle($request);
    }
}
