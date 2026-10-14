<?php

namespace App\Application\Ports;

interface TokenIssuer
{
    /** @return array{access_token: string, token_type: string, expires_in: int} */
    public function issue(string $userId, string $role): array;

    /** @return array{sub: string, role: string} */
    public function parse(string $token): array;
}
