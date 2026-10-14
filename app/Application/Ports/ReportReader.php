<?php

namespace App\Application\Ports;

use App\Domain\ValueObjects\DateRange;

interface ReportReader
{
    /** @return list<array{book_id: string, book_title: string, category_name: string, times_borrowed: int}> */
    public function mostBorrowed(DateRange $range): array;
}
