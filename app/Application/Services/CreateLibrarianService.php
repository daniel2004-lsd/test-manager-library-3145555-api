<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use App\Domain\Exceptions\UsernameTakenException;
use App\Domain\Exceptions\ValidationException;

final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {}

    /** @return array{id: string, username: string, role: string} */
    public function execute(string $username, string $password, string $role): array
    {
        if ($role !== 'librarian') {
            throw new ValidationException('role', 'Solo se puede crear bibliotecarios (role = "librarian").');
        }

        if (mb_strlen($password) < 8) {
            throw new ValidationException('password', 'La contraseña debe tener al menos 8 caracteres.');
        }

        $user = User::create($username, 'librarian');

        if ($this->users->findCredentialsByUsername($user->username) !== null) {
            throw new UsernameTakenException;
        }

        $this->users->insert($user, $this->hasher->hash($password));

        return ['id' => $user->id, 'username' => $user->username, 'role' => $user->role];
    }
}
