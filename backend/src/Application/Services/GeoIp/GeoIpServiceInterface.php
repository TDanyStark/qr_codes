<?php

declare(strict_types=1);

namespace App\Application\Services\GeoIp;

interface GeoIpServiceInterface
{
    /**
     * Lookup IP and return associative array with keys: ip, city, country, country_code, region, latitude, longitude (optional keys may be null)
     */
    public function lookup(string $ip): array;
}
