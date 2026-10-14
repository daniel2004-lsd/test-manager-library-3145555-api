<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\ReportReader;
use App\Domain\ValueObjects\DateRange;
use Illuminate\Support\Facades\DB;

final class PgsqlReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        return DB::table('loan_item as li')
            ->join('loan as l', 'l.id', '=', 'li.loan_id')
            ->where('l.loaned_at', '>=', $range->startInclusive()->format('Y-m-d\TH:i:s\Z'))
            ->where('l.loaned_at', '<', $range->endExclusiveNextDay()->format('Y-m-d\TH:i:s\Z'))
            ->groupBy('li.book_id', 'li.book_title', 'li.category_name')
            ->selectRaw('li.book_id as book_id, li.book_title as book_title,
                li.category_name as category_name, count(*)::int as times_borrowed')
            ->orderByDesc('times_borrowed')
            ->orderBy('li.book_title')
            ->get()
            ->map(static fn (object $row): array => [
                'book_id' => (string) $row->book_id,
                'book_title' => (string) $row->book_title,
                'category_name' => (string) $row->category_name,
                'times_borrowed' => (int) $row->times_borrowed,
            ])
            ->all();
    }
}
