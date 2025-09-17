<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepository;
use PDO;

class PdoUserRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, email, password, created_at FROM users ORDER BY id');
        $rows = $stmt->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            // Map DB fields to domain User constructor (username/firstName/lastName expectations)
            // We'll use email as username if username field isn't present in DB schema.
            $username = $row['email'] ?? $row['name'];
            // Split name into first/last when possible
            $first = $row['name'] ?? '';
            $last = '';
            if (strpos($first, ' ') !== false) {
                [$firstPart, $lastPart] = explode(' ', $first, 2);
                $first = $firstPart;
                $last = $lastPart;
            }

            $users[] = new User((int)$row['id'], $username, $first, $last);
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function findUserOfId(int $id): User
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, password, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new UserNotFoundException();
        }

        $username = $row['email'] ?? $row['name'];
        $first = $row['name'] ?? '';
        $last = '';
        if (strpos($first, ' ') !== false) {
            [$firstPart, $lastPart] = explode(' ', $first, 2);
            $first = $firstPart;
            $last = $lastPart;
        }

        return new User((int)$row['id'], $username, $first, $last);
    }

    /**
     * Find a user by email
     */
    public function findByEmail(string $email): User
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, password, created_at FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new UserNotFoundException();
        }

        $username = $row['email'] ?? $row['name'];
        $first = $row['name'] ?? '';
        $last = '';
        if (strpos($first, ' ') !== false) {
            [$firstPart, $lastPart] = explode(' ', $first, 2);
            $first = $firstPart;
            $last = $lastPart;
        }

        return new User((int)$row['id'], $username, $first, $last);
    }

    /**
     * Update password hash (or null to clear)
     */
    public function updatePassword(int $id, ?string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $passwordHash, 'id' => $id]);
    }

    public function getPasswordHashByEmail(string $email): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $row['password'] ?? null;
    }
}
