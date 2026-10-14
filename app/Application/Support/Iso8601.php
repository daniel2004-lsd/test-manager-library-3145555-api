<?php

namespace App\Application\Support;

use DateTimeImmutable;
use DateTimeZone;

final class Iso8601
{
    public static function format(?DateTimeImmutable $moment): ?string
    {
        return $moment?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /** Convierte el timestamp que devuelve PostgreSQL (`Y-m-d H:i:s+00`) a DateTimeImmutable UTC. */
    public static function parseDb(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    public static function parseInput(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
