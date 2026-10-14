<?php

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\ValidationException;
use App\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function test_total_minimo_es_uno(): void
    {
        $this->assertSame(1, Quantity::total(1)->value);
    }

    public function test_total_cero_es_invalido(): void
    {
        $this->expectException(ValidationException::class);
        Quantity::total(0);
    }

    public function test_disponibles_dentro_del_rango(): void
    {
        $this->assertSame(0, Quantity::available(0, 3)->value);
        $this->assertSame(3, Quantity::available(3, 3)->value);
    }

    public function test_disponibles_negativos_es_invalido(): void
    {
        $this->expectException(ValidationException::class);
        Quantity::available(-1, 3);
    }

    public function test_disponibles_mayor_que_el_total_es_invalido(): void
    {
        $this->expectException(ValidationException::class);
        Quantity::available(4, 3);
    }
}
