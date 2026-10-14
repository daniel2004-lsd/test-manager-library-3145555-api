<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_salud_responde_ok_en_las_dos_rutas(): void
    {
        $this->get('/health')->assertOk()->assertJson(['status' => 'ok']);
        $this->getJson('/api/v1/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_login_devuelve_token_con_expiracion_de_8_horas(): void
    {
        $this->ensureUser('admin', 'Admin!2026', 'admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'Admin!2026',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'role'])
            ->assertJson(['token_type' => 'Bearer', 'expires_in' => 28800, 'role' => 'admin']);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_credenciales_invalidas_dan_401_generico(): void
    {
        $this->ensureUser('admin', 'Admin!2026', 'admin');

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'otra-clave',
        ]);
        $wrongPassword->assertStatus(401)->assertJson(['code' => 'INVALID_CREDENTIALS']);

        $unknown = $this->postJson('/api/v1/auth/login', [
            'username' => 'nadie',
            'password' => 'secreto123',
        ]);
        $unknown->assertStatus(401)
            ->assertJson(['code' => 'INVALID_CREDENTIALS', 'message' => 'Usuario o contraseña incorrectos.']);
    }

    public function test_sin_token_o_token_invalido_dan_401(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401)->assertJson(['code' => 'UNAUTHENTICATED']);
        $this->getJson('/api/v1/books', ['Authorization' => 'Bearer token-falso'])
            ->assertStatus(401)
            ->assertJson(['code' => 'UNAUTHENTICATED']);
    }

    public function test_librerian_no_puede_crear_usuarios(): void
    {
        $token = $this->librarianToken();

        $this->postJson('/api/v1/users', [
            'username' => 'otro',
            'password' => 'secreto123',
            'role' => 'librarian',
        ], $this->auth($token))->assertStatus(403)->assertJson(['code' => 'FORBIDDEN_ROLE']);
    }
}
