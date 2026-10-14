<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class ReportTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_solo_admin(): void
    {
        $token = $this->librarianToken();

        $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $this->auth($token))
            ->assertStatus(403)
            ->assertJson(['code' => 'FORBIDDEN_ROLE']);
    }

    public function test_rango_invertido_dan_422(): void
    {
        $token = $this->adminToken();

        $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-31&to=2026-10-01', $this->auth($token))
            ->assertStatus(422)
            ->assertJson(['code' => 'INVALID_DATE_RANGE']);
    }

    public function test_fechas_obligatorias(): void
    {
        $token = $this->adminToken();

        $this->getJson('/api/v1/reports/most-borrowed', $this->auth($token))->assertStatus(422);
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01', $this->auth($token))->assertStatus(422);
    }

    public function test_rango_sin_prestamos_devuelve_filas_vacias(): void
    {
        $token = $this->adminToken();

        $this->getJson('/api/v1/reports/most-borrowed?from=2027-01-01&to=2027-01-31', $this->auth($token))
            ->assertOk()
            ->assertJson(['from' => '2027-01-01', 'to' => '2027-01-31', 'rows' => []]);
    }

    public function test_agrupa_congelado_y_ordena_por_veces_despues_por_titulo(): void
    {
        $token = $this->adminToken();
        $bookA = $this->createBook($token, ['title' => 'Alfa', 'total_copies' => 3]);
        $bookB = $this->createBook($token, ['title' => 'Beta']);
        $member1 = $this->createMember($token);
        $member2 = $this->createMember($token);

        $this->lend($token, $member1['id'], [$bookA['id'], $bookB['id']], '2026-10-05T12:00:00Z');
        $this->lend($token, $member2['id'], [$bookA['id']], '2026-10-06T12:00:00Z');
        $this->lend($token, $member2['id'], [$bookA['id']], '2026-09-06T12:00:00Z');

        $report = $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $this->auth($token));
        $report->assertOk()->assertJsonPath('from', '2026-10-01')->assertJsonPath('to', '2026-10-31');

        $rows = $report->json('rows');
        $this->assertCount(2, $rows);
        $this->assertSame('Alfa', $rows[0]['book_title']);
        $this->assertSame(2, $rows[0]['times_borrowed']);
        $this->assertSame('Beta', $rows[1]['book_title']);

        $this->putJson("/api/v1/books/{$bookA['id']}", [
            'title' => 'Alfa Renombrado',
            'author' => 'Autor',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 5,
        ], $this->auth($token))->assertOk();

        $member3 = $this->createMember($token);
        $this->lend($token, $member3['id'], [$bookA['id']], '2026-10-07T12:00:00Z');

        $after = $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $this->auth($token))
            ->json('rows');

        $titlesForBookA = array_values(array_filter($after, static fn (array $row): bool => $row['book_id'] === $bookA['id']));
        $this->assertCount(2, $titlesForBookA, 'Los datos congelados deben producir dos filas tras renombrar el libro (ADR-004)');
        $frozenTitles = array_column($titlesForBookA, 'book_title');
        sort($frozenTitles);
        $this->assertSame(['Alfa', 'Alfa Renombrado'], $frozenTitles);
    }
}
