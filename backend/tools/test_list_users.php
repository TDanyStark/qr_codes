<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Application\Actions\User\ListUsersAction;
use Psr\Log\LoggerInterface;
use App\Domain\User\UserRepository;

// Load simple .env (key=value) into environment if present
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        if ($k !== '') {
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// Build container like in public/index.php
$containerBuilder = new ContainerBuilder();
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);
$container = $containerBuilder->build();

// Resolve action
/** @var ListUsersAction $action */
$action = $container->get(ListUsersAction::class);

// Call the protected action method via reflection (since Action->action is protected)
$ref = new ReflectionClass($action);
$method = $ref->getMethod('action');
$method->setAccessible(true);

try {
    $response = $method->invoke($action);
    // Response is a Psr Response; output body
    $body = $response->getBody();
    $body->rewind();
    echo "Response body:\n";
    echo $body->getContents();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
