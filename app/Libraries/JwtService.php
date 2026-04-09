<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected string $secret;
    protected string $issuer;
    protected int $ttlSeconds;

    public function __construct()
    {
        $envSecret = env('auth.jwtSecret');
        $fallback = (string) (env('encryption.key') ?? APP_NAMESPACE . '_jwt_dev_secret_change_me');

        $this->secret = is_string($envSecret) && trim($envSecret) !== '' ? $envSecret : $fallback;
        $this->issuer = (string) (env('auth.jwtIssuer') ?: (base_url('/') ?: 'fooddash-api'));
        $this->ttlSeconds = (int) (env('auth.jwtTtl') ?: 3600);
    }

    public function createAccessToken(string $userType, int $userId, string $email, int $tokenVersion = 0): string
    {
        $now = time();

        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttlSeconds,
            'sub' => (string) $userId,
            'uid' => $userId,
            'type' => $userType,
            'email' => $email,
            'v' => $tokenVersion,
            'jti' => bin2hex(random_bytes(16)),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function decodeAccessToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
