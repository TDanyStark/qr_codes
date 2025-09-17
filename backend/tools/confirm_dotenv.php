<?php
require __DIR__ . '/../vendor/autoload.php';

function dumpEnvFile($path) {
    if (!file_exists($path)) {
        echo "No env file at: $path\n";
        return;
    }
    echo "Contents of $path:\n";
    echo file_get_contents($path) . "\n";
}

$backendEnv = __DIR__ . '/../.env';
$rootEnv = __DIR__ . '/../../.env';

dumpEnvFile($backendEnv);
dumpEnvFile($rootEnv);

if (file_exists($backendEnv)) {
    echo "Loading backend .env from: $backendEnv\n";
    \Dotenv\Dotenv::createUnsafeImmutable(dirname($backendEnv))->safeLoad();
} elseif (file_exists($rootEnv)) {
    echo "Loading root .env from: $rootEnv\n";
    \Dotenv\Dotenv::createUnsafeImmutable(dirname($rootEnv))->safeLoad();
} else {
    echo "No .env found to load.\n";
}

echo 'After load: DB_USERNAME=' . (getenv('DB_USERNAME') ?: 'EMPTY') . "\n";
