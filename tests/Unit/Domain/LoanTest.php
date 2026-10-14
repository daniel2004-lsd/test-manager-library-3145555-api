<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Loan;
use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\ItemAlreadyReturnedException;
use App\Domain\Exceptions\ValidationException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LoanTest extends TestCase
{
    private function books(int $count): array
    {
        return array_map(static fn (int $i): array => [
            'book_id' => "00000000-0000-4000-8000-00000000000{$i}",
            'book_title' => "Libro {$i}",
            'isbn' => '978842050000'.$i,
            'category_name' => 'Novela',
        ], range(1, $count));
    }

    public function test_prestamo_de_uno_a_tres_libros(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-14T12:00:00Z');

        $one = Loan::register('m1', 'u1', $loanedAt, $this->books(1));
        $three = Loan::register('m1', 'u1', $loanedAt, $this->books(3));

        $this->assertCount(1, $one->items);
        $this->assertCount(3, $three->items);
    }

    public function test_cero_libros_es_invalido(): void
    {
        $this->expectException(ValidationException::class);
        Loan::register('m1', 'u1', new DateTimeImmutable, []);
    }

    public function test_cuatro_libros_es_invalido(): void
    {
        $this->expectException(ValidationException::class);
        Loan::register('m1', 'u1', new DateTimeImmutable, $this->books(4));
    }

    public function test_libro_repetido_es_invalido(): void
    {
        $books = [$this->books(1)[0], $this->books(1)[0]];

        $this->expectException(DuplicateBookInLoanException::class);
        Loan::register('m1', 'u1', new DateTimeImmutable, $books);
    }

    public function test_vence_a_los_14_dias(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-14T12:00:00Z');
        $loan = Loan::register('m1', 'u1', $loanedAt, $this->books(1));

        $this->assertSame('2026-10-28T12:00:00Z', $loan->items[0]->dueAt->format('Y-m-d\TH:i:s\Z'));
    }

    public function test_estado_abierto_vencido_y_cerrado(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-14T12:00:00Z');
        $loan = Loan::register('m1', 'u1', $loanedAt, $this->books(2));
        $now = new DateTimeImmutable('2026-10-20T12:00:00Z');

        $this->assertSame('open', $loan->status($now));

        $late = Loan::register('m1', 'u1', new DateTimeImmutable('2026-09-01T12:00:00Z'), $this->books(1));
        $this->assertSame('overdue', $late->status($now));

        $loan->items[0]->markReturned($now);
        $this->assertSame('open', $loan->status($now));
        $loan->items[1]->markReturned($now);
        $this->assertSame('closed', $loan->status($now));
    }

    public function test_dias_de_retraso_cuenta_dias_completos_al_ceil(): void
    {
        $loan = Loan::register('m1', 'u1', new DateTimeImmutable('2026-10-01T12:00:00Z'), $this->books(1));

        $this->assertSame(1, $loan->items[0]->daysLate(new DateTimeImmutable('2026-10-16T00:00:00Z')));
        $this->assertSame(2, $loan->items[0]->daysLate(new DateTimeImmutable('2026-10-17T12:00:00Z')));
    }

    public function test_devolver_dos_veces_es_invalido(): void
    {
        $loan = Loan::register('m1', 'u1', new DateTimeImmutable, $this->books(1));
        $now = new DateTimeImmutable;

        $loan->items[0]->markReturned($now);

        $this->expectException(ItemAlreadyReturnedException::class);
        $loan->items[0]->markReturned($now);
    }
}
