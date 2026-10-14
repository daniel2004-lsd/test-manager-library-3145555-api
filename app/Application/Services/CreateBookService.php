<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Domain\Entities\Book;
use App\Domain\Exceptions\IsbnTakenException;

final class CreateBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $title, string $author, string $isbn, string $categoryId, int $totalCopies): array
    {
        $book = Book::create($title, $author, $isbn, $categoryId, $totalCopies);

        if ($this->books->findByIsbnActive($book->isbn->value) !== null) {
            throw new IsbnTakenException($book->isbn->value);
        }

        $this->books->insert($book);

        return $this->present($book);
    }

    /** @return array<string, mixed> */
    public function present(Book $book): array
    {
        $category = null;
        foreach ($this->categories->all() as $candidate) {
            if ($candidate->id === $book->categoryId) {
                $category = $candidate->toArray();
                break;
            }
        }

        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn->value,
            'category' => $category ?? ['id' => $book->categoryId, 'name' => ''],
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
        ];
    }
}
