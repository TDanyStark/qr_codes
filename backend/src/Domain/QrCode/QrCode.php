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

    private ?string $foreground;

    private ?string $background;

    private ?string $ownerName;

    private ?string $ownerEmail;

    private ?\DateTimeImmutable $createdAt;
    
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        string $token,
        int $ownerUserId,
        string $targetUrl,
        ?string $name = null,
        ?string $foreground = null,
        ?string $background = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?string $ownerName = null,
        ?string $ownerEmail = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->ownerUserId = $ownerUserId;
        $this->targetUrl = $targetUrl;
        $this->name = $name;
        $this->foreground = $foreground;
        $this->background = $background;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        // if updatedAt not provided, default to createdAt
        $this->updatedAt = $updatedAt ?? $this->createdAt;
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

    public function getForeground(): ?string
    {
        return $this->foreground;
    }

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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
            'foreground' => $this->foreground,
            'background' => $this->background,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
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
