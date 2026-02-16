<?php

declare(strict_types=1);

namespace App\Domain\QrSubscription;

use JsonSerializable;

class QrSubscription implements JsonSerializable
{
    private ?int $id;

    private int $qrCodeId;

    private int $userId;

    private ?\DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        int $qrCodeId,
        int $userId,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->qrCodeId = $qrCodeId;
        $this->userId = $userId;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQrCodeId(): int
    {
        return $this->qrCodeId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'qrcode_id' => $this->qrCodeId,
            'user_id' => $this->userId,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
