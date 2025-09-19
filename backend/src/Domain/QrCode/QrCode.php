<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use JsonSerializable;

class QrCode implements JsonSerializable
{
    private ?int $id;

    private string $token;

    private int $ownerUserId;

    private string $targetUrl;

    private ?string $name;

    private ?string $ownerName;

    private ?string $ownerEmail;

    private ?\DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        string $token,
        int $ownerUserId,
        string $targetUrl,
        ?string $name = null,
        ?\DateTimeImmutable $createdAt = null,
        ?string $ownerName = null,
        ?string $ownerEmail = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->ownerUserId = $ownerUserId;
        $this->targetUrl = $targetUrl;
        $this->name = $name;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->ownerName = $ownerName;
        $this->ownerEmail = $ownerEmail;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getOwnerUserId(): int
    {
        return $this->ownerUserId;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function getOwnerEmail(): ?string
    {
        return $this->ownerEmail;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            
            'token' => $this->token,
            'owner_user_id' => $this->ownerUserId,
            'target_url' => $this->targetUrl,
            'name' => $this->name,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'owner_name' => $this->ownerName,
            'owner_email' => $this->ownerEmail,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

