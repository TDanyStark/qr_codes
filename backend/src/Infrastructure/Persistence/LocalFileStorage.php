<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Services\QrCode\FileStorageInterface;

class LocalFileStorage implements FileStorageInterface
{
    private string $publicDir;

    public function __construct()
    {
        // resolve backend root relative to this file
        $dir = __DIR__;
        $backendRoot = null;
        while ($dir && $dir !== dirname($dir)) {
            if (basename($dir) === 'backend') {
                $backendRoot = $dir;
                break;
            }
            $dir = dirname($dir);
        }

        if ($backendRoot === null) {
            $this->publicDir = dirname(__DIR__, 5) . '/public';
        } else {
            $this->publicDir = $backendRoot . '/public';
        }
    }

    public function save(string $relativePath, string $contents): void
    {
        $relativePath = ltrim($relativePath, '/');
        $fullPath = $this->publicDir . '/' . $relativePath;
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($fullPath, $contents);
    }
}
