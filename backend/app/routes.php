<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\CreateUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

use \App\Application\Actions\Auth\SendLoginCodeAction;
use \App\Application\Actions\Auth\VerifyLoginCodeAction;
use \App\Application\Middleware\JwtAuthMiddleware;
use \App\Application\Middleware\AdminRoleMiddleware;
use \App\Application\Actions\QrCode\ListQrCodesAction;
use \App\Application\Actions\QrCode\CreateQrCodeAction;
use \App\Application\Actions\QrCode\RedirectQrCodeAction;
use \App\Application\Actions\QrCode\ViewQrCodeAction;
use App\Application\Actions\QrCode\EditQrCodeAction;
use \App\Application\Actions\QrCode\StatsQrCodeAction;
use \App\Application\Actions\QrCode\StatsQrCodeCsvAction;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->group('/api', function (Group $group) {
        $group->group('/login', function (Group $group) {
            $group->post('/code', SendLoginCodeAction::class);
            $group->post('/code/verify', VerifyLoginCodeAction::class);
        });

        $group->group('/users', function (Group $group) {
            $group->get('', ListUsersAction::class);
            $group->post('', CreateUserAction::class);
            $group->get('/{id}', ViewUserAction::class);
        })
            ->add(AdminRoleMiddleware::class)
            ->add(JwtAuthMiddleware::class);

        // QR Codes endpoints
        $group->group('/qrcodes', function (Group $group) {
            $group->get('', ListQrCodesAction::class);
            $group->get('/{id}', ViewQrCodeAction::class);
            $group->get('/{id}/stats', StatsQrCodeAction::class);
            $group->get('/{id}/stats/csv', StatsQrCodeCsvAction::class);
            $group->post('/{id}/edit', EditQrCodeAction::class);
            $group->post('', CreateQrCodeAction::class);
        })->add(JwtAuthMiddleware::class);

        // Token verify endpoint - returns 200 if token is valid (middleware will reject otherwise)
        $group->get('/token/verify', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['ok' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        })->add(JwtAuthMiddleware::class);
    });

    // Public redirect endpoint for QR token scans
    $app->get('/r/{code}', RedirectQrCodeAction::class);
};
