<?php

namespace Tests\Concerns;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Entities\User;

trait InteractsWithApi
{
    protected function ensureUser(string $username, string $password, string $role): string
    {
        $normalized = User::normalizeUsername($username);
        $users = app(UserRepository::class);
        $credentials = $users->findCredentialsByUsername($normalized);

        if ($credentials === null) {
            $users->insert(
                User::create($normalized, $role),
                app(PasswordHasher::class)->hash($password),
            );

            $credentials = $users->findCredentialsByUsername($normalized);
        }

        return $credentials['user']->id;
    }

    protected function login(string $username, string $password): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'username' => $username,
            'password' => $password,
        ])->json('access_token');
    }

    protected function adminToken(): string
    {
        $this->ensureUser('admin', 'Admin!2026', 'admin');

        return $this->login('admin', 'Admin!2026');
    }

    protected function librarianToken(): string
    {
        $this->ensureUser('ana', 'Secreta123', 'librarian');

        return $this->login('ana', 'Secreta123');
    }

    protected function auth(string $token): array
    {
        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    /** @return array<string, mixed> */
    protected function createBook(string $token, array $overrides = []): array
    {
        static $sequence = 0;
        $sequence++;

        $payload = array_merge([
            'title' => "Libro {$sequence}",
            'author' => "Autor {$sequence}",
            'isbn' => str_pad((string) (9788420500000 + $sequence), 13, '0'),
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 2,
        ], $overrides);

        $response = $this->postJson('/api/v1/books', $payload, $this->auth($token));
        $response->assertCreated();

        return $response->json();
    }

    /** @return array<string, mixed> */
    protected function createMember(string $token, array $overrides = []): array
    {
        static $sequence = 0;
        $sequence++;

        $response = $this->postJson('/api/v1/members', array_merge([
            'full_name' => "Socio {$sequence}",
            'document_number' => "DOC-{$sequence}",
        ], $overrides), $this->auth($token));
        $response->assertCreated();

        return $response->json();
    }

    /** @return array<string, mixed> */
    protected function lend(string $token, string $memberId, array $bookIds, ?string $loanedAt = null): array
    {
        $payload = [
            'member_id' => $memberId,
            'items' => array_map(static fn (string $id): array => ['book_id' => $id], $bookIds),
        ];

        if ($loanedAt !== null) {
            $payload['loaned_at'] = $loanedAt;
        }

        $response = $this->postJson('/api/v1/loans', $payload, $this->auth($token));
        $response->assertCreated();

        return $response->json();
    }
}
