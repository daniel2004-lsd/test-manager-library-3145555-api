<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class UsersTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_admin_crea_bibliotecario_normalizado(): void
    {
        $token = $this->adminToken();

        $this->postJson('/api/v1/users', [
            'username' => '  Ana ',
            'password' => 'Secreta123',
            'role' => 'librarian',
        ], $this->auth($token))
            ->assertCreated()
            ->assertJson(['username' => 'ana', 'role' => 'librarian'])
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('password_hash');
    }

    public function test_username_duplicado_dan_409(): void
    {
        $token = $this->adminToken();
        $payload = ['username' => 'carlos', 'password' => 'Secreta123', 'role' => 'librarian'];

        $this->postJson('/api/v1/users', $payload, $this->auth($token))->assertCreated();
        $this->postJson('/api/v1/users', $payload, $this->auth($token))
            ->assertStatus(409)
            ->assertJson(['code' => 'USERNAME_TAKEN']);
    }

    public function test_pedir_rol_admin_es_422(): void
    {
        $token = $this->adminToken();

        $this->postJson('/api/v1/users', [
            'username' => 'evil',
            'password' => 'Secreta123',
            'role' => 'admin',
        ], $this->auth($token))->assertStatus(422)->assertJson(['code' => 'VALIDATION_ERROR']);
    }
}
