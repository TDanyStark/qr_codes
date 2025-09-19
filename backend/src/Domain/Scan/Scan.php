<?php

declare(strict_types=1);

namespace App\Domain\Scan;

use JsonSerializable;

class Scan implements JsonSerializable
{
    private ?int $id;
    private int $qrCodeId;
    private \DateTimeImmutable $scannedAt;
    private ?string $ip;
    private ?string $userAgent;
    private ?string $city;
    private ?string $country;

    public function __construct(
        ?int $id,
        int $qrCodeId,
        ?\DateTimeImmutable $scannedAt = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $city = null,
        ?string $country = null
    ) {
        $this->id = $id;
        $this->qrCodeId = $qrCodeId;
        $this->scannedAt = $scannedAt ?? new \DateTimeImmutable();
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->city = $city;
        $this->country = $country;
    }

    public function getId(): ?int { return $this->id; }
    public function getQrCodeId(): int { return $this->qrCodeId; }
    public function getScannedAt(): \DateTimeImmutable { return $this->scannedAt; }
    public function getIp(): ?string { return $this->ip; }
    public function getUserAgent(): ?string { return $this->userAgent; }
    public function getCity(): ?string { return $this->city; }
    public function getCountry(): ?string { return $this->country; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'qrcode_id' => $this->qrCodeId,
            'scanned_at' => $this->scannedAt->format('Y-m-d H:i:s'),
            'ip' => $this->ip,
            'user_agent' => $this->userAgent,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
