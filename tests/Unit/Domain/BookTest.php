<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Book;
use App\Domain\Exceptions\BookNotAvailableException;
use App\Domain\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function test_crear_libro_con_disponibles_igual_al_total(): void
    {
        $book = Book::create('Título', 'Autor', '9780307474728', 'cat-1', 4);

        $this->assertSame(4, $book->totalCopies);
        $this->assertSame(4, $book->availableCopies);
    }

    public function test_retirar_hasta_cero_y_luego_falla(): void
    {
        $book = Book::create('Título', 'Autor', '9780307474728', 'cat-1', 2);

        $book->withdraw();
        $book->withdraw();

        $this->assertSame(0, $book->availableCopies);

        $this->expectException(BookNotAvailableException::class);
        $book->withdraw();
    }

    public function test_restock_no_supera_el_total(): void
    {
        $book = Book::create('Título', 'Autor', '9780307474728', 'cat-1', 1);
        $book->withdraw();

        $this->expectException(ValidationException::class);

        $book->restock();
        $book->restock();
    }

    public function test_actualizar_total_ajusta_disponibles(): void
    {
        $book = Book::create('Título', 'Autor', '9780307474728', 'cat-1', 2);
        $book->withdraw();

        $book->applyUpdate('Nuevo', 'Otro', 'cat-2', 5);

        $this->assertSame(5, $book->totalCopies);
        $this->assertSame(4, $book->availableCopies);
        $this->assertSame('Nuevo', $book->title);
    }

    public function test_actualizar_total_no_puede_dejar_negativos(): void
    {
        $book = Book::create('Título', 'Autor', '9780307474728', 'cat-1', 3);
        $book->withdraw();
        $book->withdraw();

        $this->expectException(ValidationException::class);

        $book->applyUpdate('T', 'A', 'cat-1', 1);
    }
}
