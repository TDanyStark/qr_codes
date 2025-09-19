<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Services\QrCode\FileStorageInterface;
use App\Infrastructure\Utils\PublicDirectoryResolver;

class LocalFileStorage implements FileStorageInterface
{
    private string $publicDir;

    public function __construct(PublicDirectoryResolver $resolver)
    {
        $this->publicDir = $resolver->getPublicDir();
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
