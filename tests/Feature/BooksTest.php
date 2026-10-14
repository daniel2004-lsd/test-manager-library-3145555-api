<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class BooksTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    public function test_crear_libro_devuelve_la_forma_del_contrato(): void
    {
        $token = $this->adminToken();

        $this->postJson('/api/v1/books', [
            'title' => 'Cien años de soledad',
            'author' => 'Gabriel García Márquez',
            'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 4,
        ], $this->auth($token))
            ->assertCreated()
            ->assertJsonStructure(['id', 'title', 'author', 'isbn', 'category' => ['id', 'name'], 'total_copies', 'available_copies', 'cover_key'])
            ->assertJson([
                'title' => 'Cien años de soledad',
                'isbn' => '9780307474728',
                'category' => ['name' => 'Novela'],
                'total_copies' => 4,
                'available_copies' => 4,
                'cover_key' => null,
            ]);
    }

    public function test_isbn_duplicado_entre_activos_dan_409(): void
    {
        $token = $this->adminToken();
        $this->createBook($token, ['isbn' => '9780307474728']);

        $this->postJson('/api/v1/books', [
            'title' => 'Otra edición',
            'author' => 'Autor',
            'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 1,
        ], $this->auth($token))->assertStatus(409)->assertJson(['code' => 'ISBN_TAKEN']);
    }

    public function test_isbn_invalido_y_total_cero_son_422(): void
    {
        $token = $this->adminToken();
        $base = [
            'title' => 'T',
            'author' => 'A',
            'isbn' => '978030747472',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 1,
        ];

        $this->postJson('/api/v1/books', $base, $this->auth($token))
            ->assertStatus(422)
            ->assertJson(['code' => 'VALIDATION_ERROR']);

        $this->postJson('/api/v1/books', array_merge($base, ['isbn' => '9780307474728', 'total_copies' => 0]), $this->auth($token))
            ->assertStatus(422)
            ->assertJsonPath('details.0.field', 'total_copies');
    }

    public function test_baja_logica_y_reuso_de_isbn(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token, ['isbn' => '9780307474728']);

        $this->deleteJson("/api/v1/books/{$book['id']}", [], $this->auth($token))->assertNoContent();
        $this->getJson("/api/v1/books/{$book['id']}", $this->auth($token))->assertStatus(404);

        $this->postJson('/api/v1/books', [
            'title' => 'Reimpresión',
            'author' => 'Autor',
            'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 1,
        ], $this->auth($token))->assertCreated();
    }

    public function test_no_se_puede_bajar_libro_con_prestamos_abiertos(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);
        $member = $this->createMember($token);
        $this->lend($token, $member['id'], [$book['id']]);

        $this->deleteJson("/api/v1/books/{$book['id']}", [], $this->auth($token))
            ->assertStatus(409)
            ->assertJson(['code' => 'BOOK_HAS_OPEN_LOANS']);
    }

    public function test_busqueda_paginacion_y_tope_de_size(): void
    {
        $token = $this->adminToken();
        $this->createBook($token, ['title' => 'Algoritmos', 'author' => 'Cormen']);
        $this->createBook($token, ['title' => 'Cocina', 'author' => 'Karlos Arguiñano']);

        $found = $this->getJson('/api/v1/books?q=algoritmos&page=1&size=1', $this->auth($token));
        $found->assertOk()
            ->assertJsonPath('page', 1)
            ->assertJsonPath('size', 1)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.title', 'Algoritmos');

        $this->getJson('/api/v1/books?size=51', $this->auth($token))
            ->assertStatus(422)
            ->assertJson(['code' => 'VALIDATION_ERROR']);
    }

    public function test_put_reemplaza_sin_tocar_isbn_ni_disponibles_invalidos(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token, ['total_copies' => 2]);
        $member = $this->createMember($token);
        $this->lend($token, $member['id'], [$book['id']]);

        $this->putJson("/api/v1/books/{$book['id']}", [
            'title' => 'Título nuevo',
            'author' => 'Autor nuevo',
            'category_id' => '22222222-2222-4222-8222-222222222222',
            'total_copies' => 5,
        ], $this->auth($token))
            ->assertOk()
            ->assertJson([
                'title' => 'Título nuevo',
                'isbn' => $book['isbn'],
                'total_copies' => 5,
                'available_copies' => 4,
            ]);

        $this->putJson("/api/v1/books/{$book['id']}", [
            'title' => 'X',
            'author' => 'Y',
            'category_id' => '22222222-2222-4222-8222-222222222222',
            'total_copies' => 1,
        ], $this->auth($token))
            ->assertOk();

        $bookB = $this->createBook($token, ['total_copies' => 2]);
        $memberB = $this->createMember($token);
        $this->lend($token, $member['id'], [$bookB['id']]);
        $this->lend($token, $memberB['id'], [$bookB['id']]);

        $this->putJson("/api/v1/books/{$bookB['id']}", [
            'title' => 'Z',
            'author' => 'Y',
            'category_id' => '22222222-2222-4222-8222-222222222222',
            'total_copies' => 1,
        ], $this->auth($token))
            ->assertStatus(422)
            ->assertJsonPath('details.0.field', 'total_copies');
    }

    public function test_bibliotecario_no_puede_crear_libros(): void
    {
        $token = $this->librarianToken();

        $this->postJson('/api/v1/books', [
            'title' => 'T',
            'author' => 'A',
            'isbn' => '9780307474728',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 1,
        ], $this->auth($token))->assertStatus(403)->assertJson(['code' => 'FORBIDDEN_ROLE']);
    }
}
