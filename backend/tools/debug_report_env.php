<?php

declare(strict_types=1);

use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env (backend/ first, then repo root)
$envPath = null;
if (file_exists(__DIR__ . '/../.env')) {
    $envPath = __DIR__ . '/..';
} elseif (file_exists(__DIR__ . '/../../.env')) {
    $envPath = __DIR__ . '/../../';
}

if ($envPath !== null) {
    $dotenv = Dotenv\Dotenv::createUnsafeImmutable($envPath);
    $dotenv->safeLoad();
}

$containerBuilder = new ContainerBuilder();

$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

$container = $containerBuilder->build();

$envDbName = $_ENV['DB_DATABASE'] ?? 'null';
$envDbHost = $_ENV['DB_HOST'] ?? 'null';
$envDbUser = $_ENV['DB_USERNAME'] ?? 'null';

$pdo = $container->get(PDO::class);
$activeDb = $pdo->query('SELECT DATABASE()')->fetchColumn();

$paths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
];

$foundEnv = 'none';
foreach ($paths as $path) {
    if (file_exists($path)) {
        $foundEnv = $path;
        break;
    }
}

echo "ENV_FILE={$foundEnv}\n";
echo "ENV_DB_HOST={$envDbHost}\n";
echo "ENV_DB_NAME={$envDbName}\n";
echo "ENV_DB_USER={$envDbUser}\n";
echo "DB_ACTIVE={$activeDb}\n";
