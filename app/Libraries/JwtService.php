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

        if (class_exists(JWT::class)) {
            return JWT::encode($payload, $this->secret, 'HS256');
        }

        return $this->encodeFallback($payload);
    }

    public function decodeAccessToken(string $token): ?array
    {
        try {
            if (class_exists(JWT::class) && class_exists(Key::class)) {
                $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
                return (array) $decoded;
            }

            return $this->decodeFallback($token);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function encodeFallback(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $headerPart = $this->base64UrlEncode(json_encode($header));
        $payloadPart = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', $headerPart . '.' . $payloadPart, $this->secret, true);
        $signaturePart = $this->base64UrlEncode($signature);

        return $headerPart . '.' . $payloadPart . '.' . $signaturePart;
    }

    protected function decodeFallback(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerPart, $payloadPart, $signaturePart] = $parts;

        $headerJson = $this->base64UrlDecode($headerPart);
        $payloadJson = $this->base64UrlDecode($payloadPart);
        $signature = $this->base64UrlDecode($signaturePart);

        if ($headerJson === null || $payloadJson === null || $signature === null) {
            return null;
        }

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        if (($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $headerPart . '.' . $payloadPart, $this->secret, true);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $now = time();

        if (isset($payload['nbf']) && (int) $payload['nbf'] > $now) {
            return null;
        }

        if (isset($payload['exp']) && (int) $payload['exp'] <= $now) {
            return null;
        }

        return $payload;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $padding = strlen($padded) % 4;

        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
