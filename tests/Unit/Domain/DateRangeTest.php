<?php

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvalidDateRangeException;
use App\Domain\ValueObjects\DateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_rango_invertido_es_invalido(): void
    {
        $this->expectException(InvalidDateRangeException::class);
        DateRange::of(
            new DateTimeImmutable('2026-10-31'),
            new DateTimeImmutable('2026-10-01'),
        );
    }

    public function test_mismo_dia_es_valido(): void
    {
        $day = new DateTimeImmutable('2026-10-14');
        $range = DateRange::of($day, $day);

        $this->assertTrue($range->containsInclusiveDay(new DateTimeImmutable('2026-10-14T23:59:59Z')));
    }

    public function test_el_ultimo_dia_es_inclusivo_y_el_dia_siguiente_no(): void
    {
        $range = DateRange::of(
            new DateTimeImmutable('2026-10-01'),
            new DateTimeImmutable('2026-10-31'),
        );

        $this->assertTrue($range->containsInclusiveDay(new DateTimeImmutable('2026-10-31T23:59:59Z')));
        $this->assertFalse($range->containsInclusiveDay(new DateTimeImmutable('2026-11-01T00:00:00Z')));
        $this->assertFalse($range->containsInclusiveDay(new DateTimeImmutable('2026-09-30T23:59:59Z')));
    }

    public function test_extremos_para_consulta_sql(): void
    {
        $range = DateRange::of(
            new DateTimeImmutable('2026-10-01'),
            new DateTimeImmutable('2026-10-31'),
        );

        $this->assertSame('2026-10-01', $range->startInclusive()->format('Y-m-d'));
        $this->assertSame('2026-11-01', $range->endExclusiveNextDay()->format('Y-m-d'));
    }
}
