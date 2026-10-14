<?php

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\ValidationException;
use App\Domain\ValueObjects\Isbn;
use PHPUnit\Framework\TestCase;

final class IsbnTest extends TestCase
{
    public function test_acepta_un_isbn_de_13_digitos(): void
    {
        $this->assertSame('9780307474728', Isbn::of('9780307474728')->value);
        $this->assertSame('9780307474728', Isbn::of(' 9780307474728 ')->value);
    }

    public function test_rechaza_isbn_con_menos_de_13_digitos(): void
    {
        $this->expectException(ValidationException::class);
        Isbn::of('123');
    }

    public function test_rechaza_isbn_con_letras(): void
    {
        $this->expectException(ValidationException::class);
        Isbn::of('978030747472A');
    }

    public function test_rechaza_isbn_con_14_digitos(): void
    {
        $this->expectException(ValidationException::class);
        Isbn::of('97803074747281');
    }
}
