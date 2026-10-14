<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidDateRangeException;
use DateTimeImmutable;

final class DateRange
{
    private function __construct(
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
        if ($from > $to) {
            throw new InvalidDateRangeException;
        }
    }

    public static function of(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        return new self($from, $to);
    }

    /** Rango inclusive sobre fechas calendario: from 00:00Z .. to+1d 00:00Z (exclusivo). */
    public function containsInclusiveDay(DateTimeImmutable $moment): bool
    {
        $end = $this->to->modify('+1 day');

        return $moment >= $this->from && $moment < $end;
    }

    public function startInclusive(): DateTimeImmutable
    {
        return $this->from;
    }

    public function endExclusiveNextDay(): DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }
}
