<?php

namespace App\Domain\Entities;

use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Support\Uuid;
use DateTimeImmutable;

final class Loan
{
    public const int LOAN_DAYS = 14;

    /** @var list<LoanItem> */
    public readonly array $items;

    private function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
        array $items,
    ) {
        if (count($items) < 1 || count($items) > 3) {
            throw new ValidationException('items', 'Un préstamo debe tener entre 1 y 3 libros.');
        }

        $seen = [];
        foreach ($items as $item) {
            if (isset($seen[$item->bookId])) {
                throw new DuplicateBookInLoanException($item->bookId);
            }
            $seen[$item->bookId] = true;
        }

        $this->items = array_values($items);
    }

    /**
     * @param  list<array{book_id: string, book_title: string, isbn: string, category_name: string}>  $books
     */
    public static function register(
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        array $books,
    ): self {
        $dueAt = $loanedAt->modify('+'.self::LOAN_DAYS.' days');

        $items = array_map(
            fn (array $book): LoanItem => LoanItem::freeze(
                $book['book_id'],
                $book['book_title'],
                $book['isbn'],
                $book['category_name'],
                $dueAt,
            ),
            $books,
        );

        return new self(Uuid::v4(), $memberId, $loanedByUserId, $loanedAt, $items);
    }

    public static function reconstitute(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        array $items,
    ): self {
        return new self($id, $memberId, $loanedByUserId, $loanedAt, $items);
    }

    public function status(DateTimeImmutable $now): string
    {
        $pending = array_filter($this->items, fn (LoanItem $item): bool => ! $item->isReturned());

        if ($pending === []) {
            return 'closed';
        }

        foreach ($pending as $item) {
            if ($item->isOverdue($now)) {
                return 'overdue';
            }
        }

        return 'open';
    }

    public function item(string $itemId): ?LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        return null;
    }

    public function toArray(DateTimeImmutable $now): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->memberId,
            'loaned_at' => $this->loanedAt->format('Y-m-d\TH:i:s\Z'),
            'status' => $this->status($now),
            'items' => array_map(fn (LoanItem $item): array => $item->toArray(), $this->items),
        ];
    }
}
