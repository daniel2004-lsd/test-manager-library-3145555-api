<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class LoansTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_prestamo_congela_datos_y_descuenta_disponibles(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token, ['title' => 'Cien años de soledad', 'total_copies' => 3]);
        $member = $this->createMember($token);

        $loan = $this->lend($token, $member['id'], [$book['id']]);

        $this->assertSame('open', $loan['status']);
        $this->assertStringEndsWith('Z', $loan['loaned_at']);
        $this->assertStringEndsWith('Z', $loan['items'][0]['due_at']);
        $this->assertNull($loan['items'][0]['returned_at']);
        $this->assertSame('Cien años de soledad', $loan['items'][0]['book_title']);

        $loanedAt = new \DateTimeImmutable($loan['loaned_at']);
        $this->assertSame(
            $loanedAt->modify('+14 days')->format('Y-m-d\TH:i:s\Z'),
            $loan['items'][0]['due_at'],
        );

        $this->getJson("/api/v1/books/{$book['id']}", $this->auth($token))
            ->assertJson(['available_copies' => 2]);

        $this->putJson("/api/v1/books/{$book['id']}", [
            'title' => 'Título renombrado',
            'author' => 'Otro autor',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 3,
        ], $this->auth($token))->assertOk();

        $this->getJson("/api/v1/loans/{$loan['id']}", $this->auth($token))
            ->assertOk()
            ->assertJsonPath('items.0.book_title', 'Cien años de soledad');
    }

    public function test_libro_repetido_dan_422(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token);

        $this->postJson('/api/v1/loans', [
            'member_id' => $member['id'],
            'items' => [['book_id' => $book['id']], ['book_id' => $book['id']]],
        ], $this->auth($token))->assertStatus(422)->assertJson(['code' => 'DUPLICATE_BOOK_IN_LOAN']);
    }

    public function test_limite_de_tres_activos_dan_422_con_el_conteo(): void
    {
        $token = $this->adminToken();
        $member = $this->createMember($token);
        $books = [$this->createBook($token), $this->createBook($token), $this->createBook($token), $this->createBook($token)];

        $this->lend($token, $member['id'], [$books[0]['id'], $books[1]['id'], $books[2]['id']]);

        $this->postJson('/api/v1/loans', [
            'member_id' => $member['id'],
            'items' => [['book_id' => $books[3]['id']]],
        ], $this->auth($token))
            ->assertStatus(422)
            ->assertJson(['code' => 'MEMBER_LIMIT_EXCEEDED'])
            ->assertJsonPath('details.0.issue', 'límite de 3 libros sin devolver (actual: 3)');
    }

    public function test_socio_con_vencidos_no_puede_pedir(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token);

        $this->lend($token, $member['id'], [$book['id']], '2026-09-01T12:00:00Z');

        $this->postJson('/api/v1/loans', [
            'member_id' => $member['id'],
            'items' => [['book_id' => $book['id']]],
        ], $this->auth($token))->assertStatus(409)->assertJson(['code' => 'MEMBER_HAS_OVERDUE']);
    }

    public function test_sin_ejemplares_dan_409_y_no_se_registra_nada(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token, ['total_copies' => 1]);
        $memberA = $this->createMember($token);
        $memberB = $this->createMember($token);

        $this->lend($token, $memberA['id'], [$book['id']]);

        $before = $this->getJson('/api/v1/loans', $this->auth($token))->json('total');

        $this->postJson('/api/v1/loans', [
            'member_id' => $memberB['id'],
            'items' => [['book_id' => $book['id']]],
        ], $this->auth($token))->assertStatus(409)->assertJson(['code' => 'BOOK_NOT_AVAILABLE']);

        $this->assertSame($before, $this->getJson('/api/v1/loans', $this->auth($token))->json('total'));
        $this->getJson("/api/v1/books/{$book['id']}", $this->auth($token))
            ->assertJson(['available_copies' => 0, 'total_copies' => 1]);
    }

    public function test_devolver_linea_y_no_devolverla_dos_veces(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token);
        $loan = $this->lend($token, $member['id'], [$book['id']]);
        $itemId = $loan['items'][0]['id'];

        $returned = $this->postJson("/api/v1/loans/{$loan['id']}/items/{$itemId}/return", [], $this->auth($token));
        $returned->assertOk()->assertJsonPath('returned_at', fn ($value) => $value !== null);

        $this->postJson("/api/v1/loans/{$loan['id']}/items/{$itemId}/return", [], $this->auth($token))
            ->assertStatus(409)
            ->assertJson(['code' => 'ITEM_ALREADY_RETURNED']);

        $this->getJson("/api/v1/loans/{$loan['id']}", $this->auth($token))
            ->assertJson(['status' => 'closed']);
        $this->getJson("/api/v1/books/{$book['id']}", $this->auth($token))
            ->assertJson(['available_copies' => 2]);
    }

    public function test_listado_vencido_ordenado_y_con_dias_de_retraso(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token, ['full_name' => 'Persona Vencida']);

        $loan = $this->lend($token, $member['id'], [$book['id']], '2026-09-01T12:00:00Z');

        $overdue = $this->getJson('/api/v1/loans/overdue', $this->auth($token));
        $overdue->assertOk()->assertJsonStructure(['items' => [['loan_id', 'item_id', 'book_title', 'member_name', 'due_at', 'days_late']]]);
        $this->assertSame($loan['id'], $overdue->json('items.0.loan_id'));
        $this->assertSame('Persona Vencida', $overdue->json('items.0.member_name'));
        $this->assertGreaterThanOrEqual(28, $overdue->json('items.0.days_late'));

        $this->getJson("/api/v1/loans/{$loan['id']}", $this->auth($token))->assertJson(['status' => 'overdue']);
    }

    public function test_filtros_del_listado_y_peticiones_invalidas(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token);
        $this->lend($token, $member['id'], [$book['id']], '2026-09-20T12:00:00Z');

        $this->getJson('/api/v1/loans?status=open', $this->auth($token))->assertJsonPath('total', 0);
        $this->getJson('/api/v1/loans?status=overdue', $this->auth($token))->assertJsonPath('total', 1);
        $this->getJson("/api/v1/loans?member_id={$member['id']}", $this->auth($token))->assertJsonPath('total', 1);
        $this->getJson('/api/v1/loans?from=2026-09-01&to=2026-09-30', $this->auth($token))->assertJsonPath('total', 1);
        $this->getJson('/api/v1/loans?from=2026-11-01&to=2026-11-30', $this->auth($token))->assertJsonPath('total', 0);

        $this->getJson('/api/v1/loans?status=otro', $this->auth($token))->assertStatus(422);
        $this->getJson('/api/v1/loans/11111111-2222-4333-8444-555555555555', $this->auth($token))->assertStatus(404);
        $this->deleteJson('/api/v1/loans/11111111-2222-4333-8444-555555555555', [], $this->auth($token))
            ->assertStatus(405)
            ->assertJson(['code' => 'METHOD_NOT_ALLOWED']);
    }

    public function test_prestamo_de_libro_inexistente_dan_404(): void
    {
        $token = $this->adminToken();
        $member = $this->createMember($token);

        $this->postJson('/api/v1/loans', [
            'member_id' => $member['id'],
            'items' => [['book_id' => '11111111-2222-4333-8444-555555555555']],
        ], $this->auth($token))->assertStatus(404)->assertJson(['code' => 'NOT_FOUND']);
    }
}
