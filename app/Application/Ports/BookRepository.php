<?php

namespace App\Application\Ports;

use App\Domain\Entities\Book;

interface BookRepository
{
    public function findActive(string $id): ?Book;

    public function findByIsbnActive(string $isbn): ?Book;

    /** @param list<string> $ids @return array<string, Book> */
    public function findManyActive(array $ids): array;

    /** @return array{items: list<Book>, total: int} */
    public function search(?string $q, ?string $categoryId, int $page, int $size): array;

    public function insert(Book $book): void;

    public function update(string $id, string $title, string $author, string $categoryId, int $newTotal): void;

    public function decrementAvailable(string $id): bool;

    public function incrementAvailable(string $id): void;

    public function hasOpenLoans(string $id): bool;

    public function softDelete(string $id): void;

    public function setCoverKey(string $id, ?string $coverKey): void;
}
