<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepository;
use PDO;
use Psr\Log\LoggerInterface;

class PdoUserRepository implements UserRepository
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, rol, codigo, fecha_expedicion, created_at, password FROM users ORDER BY id');
        $rows = $stmt->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $name = $row['name'] ?? '';
            $email = $row['email'] ?? '';
            $rol = $row['rol'] ?? 'user';
            $codigo = $row['codigo'] ?? null;

            $fechaExp = null;
            if (!empty($row['fecha_expedicion'])) {
                $fechaExp = new \DateTimeImmutable($row['fecha_expedicion']);
            }

            $createdAt = null;
            if (!empty($row['created_at'])) {
                $createdAt = new \DateTimeImmutable($row['created_at']);
            }

            $users[] = new User($id, $name, $email, $rol, $codigo, $fechaExp, $createdAt);
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function findUserOfId(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, rol, codigo, fecha_expedicion, created_at, password FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new UserNotFoundException();
        }

        $name = $row['name'] ?? '';
        $email = $row['email'] ?? '';
        $rol = $row['rol'] ?? 'user';
        $codigo = $row['codigo'] ?? null;

        $fechaExp = null;
        if (!empty($row['fecha_expedicion'])) {
            $fechaExp = new \DateTimeImmutable($row['fecha_expedicion']);
        }

        $createdAt = null;
        if (!empty($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at']);
        }

        return new User((int)$row['id'], $name, $email, $rol, $codigo, $fechaExp, $createdAt);
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): User
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, rol, codigo, fecha_expedicion, created_at FROM users WHERE email = :email');
        $executed = $stmt->execute(['email' => $email]);

        if ($executed === false) {
            throw new UserNotFoundException();
        }

        $row = $stmt->fetch();

        if (!$row) {
            throw new UserNotFoundException();
        }

        $name = $row['name'] ?? '';
        $rol = $row['rol'] ?? 'user';
        $codigo = $row['codigo'] ?? null;

        $fechaExp = null;
        if (!empty($row['fecha_expedicion'])) {
            try {
                $fechaExp = new \DateTimeImmutable($row['fecha_expedicion']);
            } catch (\Exception $e) {
                $fechaExp = null;
            }
        }

        $createdAt = null;
        if (!empty($row['created_at'])) {
            try {
                $createdAt = new \DateTimeImmutable($row['created_at']);
            } catch (\Exception $e) {
                $createdAt = null;
            }
        }

        return new User((int)$row['id'], $name, $email, $rol, $codigo, $fechaExp, $createdAt);
    }

    /**
     * Update password hash (or null to clear)
     */
    public function updatePassword(int $id, ?string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET codigo = :codigo WHERE id = :id');
        $stmt->execute(['codigo' => $passwordHash, 'id' => $id]);
    }

    public function getCodeByEmail(string $email): ?string
    {
        $stmt = $this->pdo->prepare('SELECT codigo FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $row['codigo'] ?? null;
    }
}
