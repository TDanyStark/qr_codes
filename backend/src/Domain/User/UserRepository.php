<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;

    /**
     * Find a user by email
     * @param string $email
     * @return User
     * @throws UserNotFoundException
     */
    public function findByEmail(string $email): User;

    /**
     * Update the password field for a user (can be null to clear)
     * @param int $id
     * @param string|null $passwordHash
     * @return void
     */
    public function updatePassword(int $id, ?string $passwordHash): void;

    /**
     * Get the stored password hash for a user by email (may be null)
     * @param string $email
     * @return string|null
     */
    public function getCodeByEmail(string $email): ?string;
}
