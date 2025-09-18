<?php

declare(strict_types=1);

namespace App\Domain\User;

use JsonSerializable;

/**
 * User domain entity matching the `users` table:
 * id, name, email, rol, codigo, fecha_expedicion, created_at
 */
class User implements JsonSerializable
{
    private ?int $id;

    private string $name;

    private string $email;

    private string $rol;

    private ?string $codigo;

    private ?\DateTimeImmutable $fechaExpedicion;

    private ?\DateTimeImmutable $createdAt;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $rol = 'user',
        ?string $codigo = null,
        ?\DateTimeImmutable $fechaExpedicion = null,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = strtolower($email);
        $this->rol = $rol;
        $this->codigo = $codigo;
        $this->fechaExpedicion = $fechaExpedicion;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRol(): string
    {
        return $this->rol;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function getFechaExpedicion(): ?\DateTimeImmutable
    {
        return $this->fechaExpedicion;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Backwards-compatible accessors used elsewhere in the app
    public function getUsername(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        // try to split name by space
        $parts = preg_split('/\s+/', $this->name);
        return $parts[0] ?? $this->name;
    }

    public function getLastName(): string
    {
        $parts = preg_split('/\s+/', $this->name);
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
            'codigo' => $this->codigo,
            'fecha_expedicion' => $this->fechaExpedicion?->format('Y-m-d'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
