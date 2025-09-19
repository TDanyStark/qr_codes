<?php

declare(strict_types=1);

namespace App\Application\Services;

class UrlBuilder
{
    public function getBaseUrl(): string
    {
        $baseUrl = getenv('URL_BASE') ?: '';
        $baseUrl = rtrim($baseUrl, '/');
        return $baseUrl;
    }

    public function buildRedirectUrl(string $token): string
    {
        $base = $this->getBaseUrl();
        return ($base !== '' ? $base : '') . '/r/' . $token;
    }

    /**
     * Returns the base token URL (used in lists)
     */
    public function getUrlBaseToken(): string
    {
        $base = $this->getBaseUrl();
        return ($base !== '' ? $base : '') . '/r/';
    }
}
