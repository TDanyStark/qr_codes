<?php

declare(strict_types=1);

namespace App\Domain\Scan;

interface ScanRepository
{
    public function create(Scan $scan): Scan;

    /**
     * Optional: list scans for a qrcode (future use)
     * @return Scan[]
     */
    public function findByQrCode(int $qrCodeId, int $limit = 50): array;

    /**
     * Return daily counts for given qrcode id for the given number of days.
     * Optionally filter by city.
     * @return array{day: string, cnt: int}[]
     */
    public function dailyCounts(int $qrCodeId, int $days = 30, ?string $city = null): array;

    /**
     * Return country breakdown for given qrcode id.
     * Optionally filter by city.
     * @return array{country: ?string, cnt: int}[]
     */
    public function countryBreakdown(int $qrCodeId, int $limit = 10, ?string $city = null): array;

    /**
     * Return city breakdown for given qrcode id.
     * Optionally filter by country.
     * @return array{city: ?string, cnt: int}[]
     */
    public function cityBreakdown(int $qrCodeId, int $limit = 10, ?string $country = null): array;

    /**
     * Return total scans number for given qrcode id.
     * Optionally filter by city.
     */
    public function totalCount(int $qrCodeId, ?string $city = null): int;
}
