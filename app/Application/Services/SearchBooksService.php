<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;

final class SearchBooksService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CreateBookService $presenter,
    ) {}

    /** @return array{items: list<array<string, mixed>>, page: int, size: int, total: int} */
    public function execute(?string $q, ?string $categoryId, int $page, int $size): array
    {
        $result = $this->books->search($q, $categoryId, $page, $size);

        return [
            'items' => array_map(fn ($book): array => $this->presenter->present($book), $result['items']),
            'page' => $page,
            'size' => $size,
            'total' => $result['total'],
        ];
    }
}
