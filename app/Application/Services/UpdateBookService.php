<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Domain\Exceptions\ResourceNotFoundException;
use App\Domain\Exceptions\ValidationException;

final class UpdateBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CategoryRepository $categories,
        private readonly CreateBookService $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $id, string $title, string $author, string $categoryId, int $totalCopies): array
    {
        $book = $this->books->findActive($id);

        if ($book === null) {
            throw new ResourceNotFoundException('Libro');
        }

        if ($totalCopies < 1) {
            throw new ValidationException('total_copies', 'El total de ejemplares debe ser al menos 1.');
        }

        $this->books->update($id, $title, $author, $categoryId, $totalCopies);

        return $this->presenter->present($this->books->findActive($id));
    }
}
