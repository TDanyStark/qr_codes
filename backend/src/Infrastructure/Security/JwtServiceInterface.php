<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

interface JwtServiceInterface
{
    /**
     * Generate a JWT token from a payload.
     *
     * @param array $payload
     * @return string
     */
    public function generate(array $payload): string;

    /**
     * Decode and validate a JWT. Throws on invalid/expired.
     *
     * @param string $token
     * @return mixed
     */
    public function decode(string $token);

    /**
     * Return secret (for advanced usage)
     *
     * @return string
     */
    public function getSecret(): string;
}
