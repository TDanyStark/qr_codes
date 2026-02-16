<?php

declare(strict_types=1);

use App\Application\Services\Reporting\ReportNotificationService;
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

/** @var ReportNotificationService $service */
$service = $container->get(ReportNotificationService::class);
$sentCount = $service->runDueReports();

echo "Report notifications sent: {$sentCount}\n";
