<?php

namespace App\Domain\Entities;

use App\Domain\Exceptions\ItemAlreadyReturnedException;
use App\Domain\Support\Uuid;
use DateTimeImmutable;

final class LoanItem
{
    private function __construct(
        public readonly string $id,
        public readonly string $loanId,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt,
    ) {}

    public static function freeze(
        string $bookId,
        string $bookTitle,
        string $isbn,
        string $categoryName,
        DateTimeImmutable $dueAt,
        ?string $id = null,
    ): self {
        return new self(
            id: $id ?? Uuid::v4(),
            loanId: '',
            bookId: $bookId,
            bookTitle: $bookTitle,
            isbn: $isbn,
            categoryName: $categoryName,
            dueAt: $dueAt,
            returnedAt: null,
        );
    }

    public static function reconstitute(
        string $id,
        string $loanId,
        string $bookId,
        string $bookTitle,
        string $isbn,
        string $categoryName,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $returnedAt,
    ): self {
        return new self($id, $loanId, $bookId, $bookTitle, $isbn, $categoryName, $dueAt, $returnedAt);
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function markReturned(DateTimeImmutable $now): void
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturnedException;
        }

        $this->returnedAt = $now;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    public function daysLate(DateTimeImmutable $now): int
    {
        $seconds = $now->getTimestamp() - $this->dueAt->getTimestamp();

        return (int) ceil($seconds / 86400);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->bookId,
            'book_title' => $this->bookTitle,
            'isbn' => $this->isbn,
            'category_name' => $this->categoryName,
            'due_at' => $this->dueAt->format('Y-m-d\TH:i:s\Z'),
            'returned_at' => $this->returnedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
