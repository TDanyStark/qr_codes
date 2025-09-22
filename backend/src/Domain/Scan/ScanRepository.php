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
     * @return array{day: string, cnt: int}[]
     */
    public function dailyCounts(int $qrCodeId, int $days = 30): array;

    /**
     * Return country breakdown for given qrcode id.
     * @return array{country: ?string, cnt: int}[]
     */
    public function countryBreakdown(int $qrCodeId, int $limit = 10): array;

    /**
     * Return total scans number for given qrcode id.
     */
    public function totalCount(int $qrCodeId): int;
}
