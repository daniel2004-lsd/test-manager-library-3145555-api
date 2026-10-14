<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class MembersTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_crear_socio_y_duplicar_documento_dan_409(): void
    {
        $token = $this->adminToken();
        $payload = ['full_name' => 'María Pérez', 'document_number' => 'CC-123'];

        $this->postJson('/api/v1/members', $payload, $this->auth($token))
            ->assertCreated()
            ->assertJson(['full_name' => 'María Pérez', 'document_number' => 'CC-123']);

        $this->postJson('/api/v1/members', $payload, $this->auth($token))
            ->assertStatus(409)
            ->assertJson(['code' => 'DOCUMENT_TAKEN']);
    }

    public function test_detalle_cuenta_prestamos_activos(): void
    {
        $token = $this->adminToken();
        $member = $this->createMember($token);
        $bookA = $this->createBook($token);
        $bookB = $this->createBook($token);

        $loan = $this->lend($token, $member['id'], [$bookA['id'], $bookB['id']]);

        $this->getJson("/api/v1/members/{$member['id']}", $this->auth($token))
            ->assertOk()
            ->assertJson(['id' => $member['id'], 'active_loans' => 2]);

        $itemId = $loan['items'][0]['id'];
        $this->postJson("/api/v1/loans/{$loan['id']}/items/{$itemId}/return", [], $this->auth($token))->assertOk();

        $this->getJson("/api/v1/members/{$member['id']}", $this->auth($token))
            ->assertOk()
            ->assertJson(['active_loans' => 1]);
    }

    public function test_socio_inexistente_dan_404(): void
    {
        $token = $this->adminToken();

        $this->getJson('/api/v1/members/11111111-2222-4333-8444-555555555555', $this->auth($token))
            ->assertStatus(404)
            ->assertJson(['code' => 'NOT_FOUND']);
    }

    public function test_los_socios_no_se_eliminan(): void
    {
        $token = $this->adminToken();
        $member = $this->createMember($token);

        $this->deleteJson("/api/v1/members/{$member['id']}", [], $this->auth($token))
            ->assertStatus(405)
            ->assertJson(['code' => 'METHOD_NOT_ALLOWED']);
    }
}
