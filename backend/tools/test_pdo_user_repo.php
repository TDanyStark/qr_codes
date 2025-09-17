<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\User\PdoUserRepository;
use PDO;

// Load .env
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

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE') ?: 'qr_codes';
$user = getenv('DB_USERNAME') ?: '';
$pass = getenv('DB_PASSWORD') ?: '';
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $repo = new PdoUserRepository($pdo);
    $users = $repo->findAll();

    echo "Found " . count($users) . " users:\n";
    foreach ($users as $u) {
        var_dump($u->jsonSerialize());
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
