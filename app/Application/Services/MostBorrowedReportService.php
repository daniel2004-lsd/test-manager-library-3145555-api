<?php

namespace App\Application\Services;

use App\Application\Ports\ReportReader;
use App\Domain\ValueObjects\DateRange;

final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $report) {}

    /** @return array{from: string, to: string, rows: list<array<string, mixed>>} */
    public function execute(DateRange $range): array
    {
        return [
            'from' => $range->from->format('Y-m-d'),
            'to' => $range->to->format('Y-m-d'),
            'rows' => array_map(
                static fn (array $row): array => [
                    'book_id' => $row['book_id'],
                    'book_title' => $row['book_title'],
                    'category_name' => $row['category_name'],
                    'times_borrowed' => (int) $row['times_borrowed'],
                ],
                $this->report->mostBorrowed($range),
            ),
        ];
    }
}
