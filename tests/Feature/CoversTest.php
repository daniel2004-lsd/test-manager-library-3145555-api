<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

final class CoversTest extends TestCase
{
    use InteractsWithApi;
    use RefreshDatabase;

    private function tinyPng(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cover');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        return new UploadedFile($path, 'portada.png', 'image/png', null, true);
    }

    public function test_subir_consultar_y_quitar_portada(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);

        $uploaded = $this->putJson("/api/v1/books/{$book['id']}/cover", ['file' => $this->tinyPng()], $this->auth($token));
        $uploaded->assertOk();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.png$/', $uploaded->json('cover_key'));

        $binary = $this->get("/api/v1/books/{$book['id']}/cover", $this->auth($token));
        $binary->assertOk();
        $this->assertSame('image/png', $binary->headers->get('Content-Type'));

        $removed = $this->deleteJson("/api/v1/books/{$book['id']}/cover", [], $this->auth($token));
        $removed->assertOk()->assertJsonPath('cover_key', null);

        $this->getJson("/api/v1/books/{$book['id']}/cover", $this->auth($token))->assertStatus(404);
    }

    public function test_tipo_validado_por_contenido_no_por_extension(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);

        $path = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($path, 'esto no es una imagen');

        $this->putJson("/api/v1/books/{$book['id']}/cover", [
            'file' => new UploadedFile($path, 'portada.png', 'image/png', null, true),
        ], $this->auth($token))
            ->assertStatus(422)
            ->assertJsonPath('details.0.field', 'file');
    }

    public function test_archivo_mayor_a_2mb_dan_422(): void
    {
        $token = $this->adminToken();
        $book = $this->createBook($token);

        $path = tempnam(sys_get_temp_dir(), 'big');
        file_put_contents($path, str_repeat('a', (2 * 1024 * 1024) + 1));

        $this->putJson("/api/v1/books/{$book['id']}/cover", [
            'file' => new UploadedFile($path, 'portada.jpg', 'image/jpeg', null, true),
        ], $this->auth($token))
            ->assertStatus(422)
            ->assertJsonPath('details.0.field', 'file');
    }

    public function test_bibliotecario_no_puede_subir_portada(): void
    {
        $token = $this->librarianToken();
        $book = $this->createBook($this->adminToken());

        $this->putJson("/api/v1/books/{$book['id']}/cover", ['file' => $this->tinyPng()], $this->auth($token))
            ->assertStatus(403)
            ->assertJson(['code' => 'FORBIDDEN_ROLE']);
    }
}
