<?php

namespace App\Application\Services;

use App\Application\Ports\LoanRepository;
use App\Application\Support\Iso8601;
use App\Domain\Exceptions\ValidationException;

final class ListLoansService
{
    private const array STATUSES = ['open', 'overdue', 'closed'];

    public function __construct(private readonly LoanRepository $loans) {}

    /** @return array{items: list<array<string, mixed>>, page: int, size: int, total: int} */
    public function execute(
        ?string $from,
        ?string $to,
        ?string $status,
        ?string $memberId,
        int $page,
        int $size,
    ): array {
        if ($status !== null && ! in_array($status, self::STATUSES, true)) {
            throw new ValidationException('status', 'El estado debe ser open, overdue o closed.');
        }

        $result = $this->loans->search(
            $from === null ? null : Iso8601::parseInput($from),
            $to === null ? null : Iso8601::parseInput($to),
            $status,
            $memberId,
            $page,
            $size,
        );

        return [
            'items' => array_map(
                static fn (array $row): array => [
                    'id' => $row['id'],
                    'member_id' => $row['member_id'],
                    'loaned_at' => Iso8601::format(Iso8601::parseDb($row['loaned_at'])),
                    'status' => $row['status'],
                ],
                $result['items'],
            ),
            'page' => $page,
            'size' => $size,
            'total' => $result['total'],
        ];
    }
}
