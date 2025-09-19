<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

interface FileStorageInterface
{
    /**
     * Save contents to the given relative path under public tmp directory.
     * Creates directories if needed.
     *
     * @param string $relativePath e.g. '/tmp/qrcodes/abc.png' or 'tmp/qrcodes/abc.png'
     * @param string $contents
     */
    public function save(string $relativePath, string $contents): void;
}
