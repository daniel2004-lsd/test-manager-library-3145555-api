<?php

namespace App\Domain\Entities;

use App\Domain\Exceptions\BookNotAvailableException;
use App\Domain\Exceptions\ValidationException;
use App\Domain\Support\Uuid;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;

final class Book
{
    private function __construct(
        public readonly string $id,
        public string $title,
        public string $author,
        public readonly Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey,
        public readonly ?DateTimeImmutable $deletedAt,
    ) {
        Quantity::total($totalCopies);
        Quantity::available($availableCopies, $totalCopies);
    }

    public static function create(
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
        ?string $id = null,
    ): self {
        $total = Quantity::total($totalCopies)->value;

        return new self(
            id: $id ?? Uuid::v4(),
            title: trim($title),
            author: trim($author),
            isbn: Isbn::of($isbn),
            categoryId: $categoryId,
            totalCopies: $total,
            availableCopies: $total,
            coverKey: null,
            deletedAt: null,
        );
    }

    public static function reconstitute(
        string $id,
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self($id, $title, $author, Isbn::of($isbn), $categoryId, $totalCopies, $availableCopies, $coverKey, $deletedAt);
    }

    public function withdraw(): void
    {
        if ($this->availableCopies <= 0) {
            throw new BookNotAvailableException($this->title);
        }

        $this->availableCopies--;
    }

    public function restock(): void
    {
        Quantity::available($this->availableCopies + 1, $this->totalCopies);
        $this->availableCopies++;
    }

    public function applyUpdate(string $title, string $author, string $categoryId, int $newTotal): void
    {
        Quantity::total($newTotal);

        $delta = $newTotal - $this->totalCopies;
        $newAvailable = $this->availableCopies + $delta;

        if ($newAvailable < 0) {
            throw new ValidationException(
                'total_copies',
                'El nuevo total dejaría los ejemplares disponibles en negativo.',
            );
        }

        $this->title = trim($title);
        $this->author = trim($author);
        $this->categoryId = $categoryId;
        $this->totalCopies = $newTotal;
        $this->availableCopies = Quantity::available($newAvailable, $newTotal)->value;
    }
}
