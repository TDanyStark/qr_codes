<?php

declare(strict_types=1);

namespace App\Domain\QrSubscription;

interface QrSubscriptionRepository
{
    public function create(int $qrCodeId, int $userId): QrSubscription;

    public function delete(int $qrCodeId, int $userId): void;

    /**
     * @return QrSubscription[]
     */
    public function listByQrCode(int $qrCodeId): array;

    /**
     * @return QrSubscription[]
     */
    public function listByUser(int $userId): array;

    /**
     * @return QrSubscription[]
     */
    public function listAll(): array;
}
