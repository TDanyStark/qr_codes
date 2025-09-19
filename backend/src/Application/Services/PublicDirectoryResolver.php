<?php

declare(strict_types=1);

namespace App\Application\Services;

class PublicDirectoryResolver
{
    public function getPublicDir(): string
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
            return dirname(__DIR__, 5) . '/public';
        }

        return $backendRoot . '/public';
    }
}
