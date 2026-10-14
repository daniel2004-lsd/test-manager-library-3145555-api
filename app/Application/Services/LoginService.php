<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCredentialsException;

final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
    ) {}

    /** @return array{access_token: string, token_type: string, expires_in: int, role: string} */
    public function execute(string $username, string $password): array
    {
        $credentials = $this->users->findCredentialsByUsername(User::normalizeUsername($username));

        if ($credentials === null || ! $this->hasher->verify($password, $credentials['password_hash'])) {
            throw new InvalidCredentialsException;
        }

        $token = $this->tokens->issue($credentials['user']->id, $credentials['user']->role);

        return [...$token, 'role' => $credentials['user']->role];
    }
}
