<?php

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException;
use App\Domain\Support\Uuid;

final class User
{
    public const array ROLES = ['admin', 'librarian'];

    private function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $role,
    ) {
        if (! in_array($role, self::ROLES, true)) {
            throw new ValidationException('role', 'El rol debe ser «admin» o «librarian».');
        }
    }

    public static function create(string $username, string $role, ?string $id = null): self
    {
        $username = self::normalizeUsername($username);

        if ($username === '') {
            throw new ValidationException('username', 'El nombre de usuario es obligatorio.');
        }

        return new self($id ?? Uuid::v4(), $username, $role);
    }

    public static function reconstitute(string $id, string $username, string $role): self
    {
        return new self($id, $username, $role);
    }

    public static function normalizeUsername(string $username): string
    {
        return mb_strtolower(trim($username));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
