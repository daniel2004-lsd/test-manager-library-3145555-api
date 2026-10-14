<?php

namespace Tests\Feature;

use App\Application\Ports\ReportReader;
use App\Domain\ValueObjects\DateRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class ErrorShapeTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_todos_los_errores_usan_el_mismo_cuerpo(): void
    {
        $token = $this->adminToken();

        $notFound = $this->getJson('/api/v1/ruta-inexistente', $this->auth($token));
        $notFound->assertStatus(404)->assertJsonStructure(['code', 'message', 'details']);

        $unauthenticated = $this->getJson('/api/v1/books');
        $unauthenticated->assertStatus(401)->assertJsonStructure(['code', 'message', 'details']);

        $validation = $this->postJson('/api/v1/members', [], $this->auth($token));
        $validation->assertStatus(422)->assertJsonStructure(['code', 'message', 'details']);
        $this->assertNotEmpty($validation->json('details'));
        $this->assertArrayHasKey('field', $validation->json('details.0'));
        $this->assertArrayHasKey('issue', $validation->json('details.0'));
    }

    public function test_error_interno_nunca_filtra_detalles(): void
    {
        $this->app->instance(ReportReader::class, new class implements ReportReader
        {
            public function mostBorrowed(DateRange $range): array
            {
                throw new \RuntimeException('fallo interno con clave secreta-123');
            }
        });

        $token = $this->adminToken();
        $response = $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $this->auth($token));

        $response->assertStatus(500)->assertJson(['code' => 'INTERNAL_ERROR', 'details' => []]);
        $this->assertStringNotContainsString('secreta-123', $response->getContent());
        $this->assertStringNotContainsString('trace', $response->getContent());
    }

    public function test_metodo_no_permitido_dan_405(): void
    {
        $token = $this->adminToken();

        $this->putJson('/api/v1/categories', [], $this->auth($token))
            ->assertStatus(405)
            ->assertJson(['code' => 'METHOD_NOT_ALLOWED']);
    }
}
