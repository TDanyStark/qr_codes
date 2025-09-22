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
     * List qrcodes with pagination and optional search query.
     *
     * @param int $page 1-based page number
     * @param int $perPage items per page
     * @param string|null $query optional search string to match token, name, target_url or owner name/email
     * @param int|null $ownerUserId if provided, restrict results to this owner (non-admin)
     * @return array{items: QrCode[], total: int}
     */
    public function list(int $page, int $perPage, ?string $query = null, ?int $ownerUserId = null): array;

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

    /**
     * Update an existing QR code record and return the updated entity
     * @param QrCode $qrCode
     * @return QrCode
     */
    public function update(QrCode $qrCode): QrCode;
}
