<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Domain\Exceptions\ResourceNotFoundException;

final class GetBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CreateBookService $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $id): array
    {
        $book = $this->books->findActive($id);

        if ($book === null) {
            throw new ResourceNotFoundException('Libro');
        }

        return $this->presenter->present($book);
    }
}
