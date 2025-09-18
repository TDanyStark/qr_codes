<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

interface QrCodeRepository
{
    /**
     * @return QrCode[]
     */
    public function findAllForUser(int $ownerUserId): array;

    /**
     * Return all QR codes in the system (for admin use)
     * @return QrCode[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return QrCode
     */
    public function findOfId(int $id): QrCode;

    /**
     * Find by token
     * @param string $token
     * @return QrCode
     */
    public function findByToken(string $token): QrCode;

    /**
     * Create a new QR code
     * @param QrCode $qrCode
     * @return QrCode
     */
    public function create(QrCode $qrCode): QrCode;
}
