<?php

namespace App\Infrastructure\Security;

use App\Application\Ports\TokenIssuer;
use App\Domain\Exceptions\UnauthenticatedException;

final class JwtTokenIssuer implements TokenIssuer
{
    public const int TTL_SECONDS = 28800;

    public function __construct(private readonly string $secret) {}

    public function issue(string $userId, string $role): array
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $userId,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + self::TTL_SECONDS,
        ], JSON_THROW_ON_ERROR));

        $signature = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true));

        return [
            'access_token' => "{$header}.{$payload}.{$signature}",
            'token_type' => 'Bearer',
            'expires_in' => self::TTL_SECONDS,
        ];
    }

    public function parse(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthenticatedException;
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true));

        if (! hash_equals($expected, $signature)) {
            throw new UnauthenticatedException;
        }

        $claims = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($claims) || ! isset($claims['sub'], $claims['role'], $claims['exp'])) {
            throw new UnauthenticatedException;
        }

        if ((int) $claims['exp'] < time()) {
            throw new UnauthenticatedException('El token ha expirado.');
        }

        return ['sub' => (string) $claims['sub'], 'role' => (string) $claims['role']];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padding = (4 - strlen($data) % 4) % 4;

        return base64_decode(strtr($data, '-_', '+/').str_repeat('=', $padding), true) ?: '';
    }
}
