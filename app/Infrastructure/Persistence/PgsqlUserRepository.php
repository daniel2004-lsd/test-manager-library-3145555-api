<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;
use Illuminate\Support\Facades\DB;

final class PgsqlUserRepository implements UserRepository
{
    public function findCredentialsByUsername(string $username): ?array
    {
        $row = DB::table('user')->where('username', $username)->first();

        if ($row === null) {
            return null;
        }

        return [
            'user' => User::reconstitute((string) $row->id, (string) $row->username, (string) $row->role),
            'password_hash' => (string) $row->password_hash,
        ];
    }

    public function findById(string $id): ?User
    {
        $row = DB::table('user')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        return User::reconstitute((string) $row->id, (string) $row->username, (string) $row->role);
    }

    public function insert(User $user, string $passwordHash): void
    {
        DB::table('user')->insert([
            'id' => $user->id,
            'username' => $user->username,
            'password_hash' => $passwordHash,
            'role' => $user->role,
        ]);
    }
}
