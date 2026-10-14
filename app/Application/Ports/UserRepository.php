<?php

namespace App\Application\Ports;

use App\Domain\Entities\User;

interface UserRepository
{
    /** @return array{user: User, password_hash: string}|null */
    public function findCredentialsByUsername(string $username): ?array;

    public function findById(string $id): ?User;

    public function insert(User $user, string $passwordHash): void;
}
