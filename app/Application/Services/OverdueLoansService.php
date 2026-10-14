<?php

namespace App\Application\Services;

use App\Application\Ports\LoanRepository;
use App\Application\Support\Iso8601;

final class OverdueLoansService
{
    public function __construct(private readonly LoanRepository $loans) {}

    /** @return array{items: list<array<string, mixed>>} */
    public function execute(): array
    {
        return [
            'items' => array_map(
                static fn (array $row): array => [
                    'loan_id' => $row['loan_id'],
                    'item_id' => $row['item_id'],
                    'book_title' => $row['book_title'],
                    'member_name' => $row['member_name'],
                    'due_at' => Iso8601::format(Iso8601::parseDb($row['due_at'])),
                    'days_late' => (int) $row['days_late'],
                ],
                $this->loans->overdueItems(),
            ),
        ];
    }
}
