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

        // Token verify endpoint - returns 200 if token is valid (middleware will reject otherwise)
        $group->get('/token/verify', function (Request $request, Response $response) {
            $response->getBody()->write(json_encode(['ok' => true]));
            return $response->withHeader('Content-Type', 'application/json');
        })->add(JwtAuthMiddleware::class);
    });

    $app->get('/r/{code}', function (Request $request, Response $response, array $args) {
        $code = $args['code'];
        // Aquí puedes implementar la lógica para buscar la URL asociada al código en tu base de datos
        // Por simplicidad, vamos a redirigir a una URL fija
        $url = 'https://www.google.com'; // Reemplaza esto con la URL real asociada al código

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    });
};
