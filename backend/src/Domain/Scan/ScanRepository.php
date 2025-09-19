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
}
