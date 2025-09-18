<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Settings\SettingsInterface;
use Firebase\JWT\JWT;

class JwtService implements JwtServiceInterface
{
    private string $secret;
    private int $expiryDays;

    public function __construct(SettingsInterface $settings)
    {
        $jwt = $settings->get('jwt');
        $this->secret = $jwt['secret'] ?? 'change_this_secret';
        $this->expiryDays = (int)($jwt['expiry_days'] ?? 15);
    }

    public function generate(array $payload): string
    {
        $now = time();
        $exp = $now + ($this->expiryDays * 24 * 60 * 60);

        $token = array_merge($payload, ['iat' => $now, 'exp' => $exp]);

        return JWT::encode($token, $this->secret, 'HS256');
    }

    /**
     * Decode and validate a JWT. Throws on invalid/expired.
     *
     * @return object
     */
    public function decode(string $token)
    {
        return JWT::decode($token, new \Firebase\JWT\Key($this->secret, 'HS256'));
    }

    /**
     * Return secret (for advanced usage)
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
