<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Domain\Exceptions\BookHasOpenLoansException;
use App\Domain\Exceptions\ResourceNotFoundException;

final class DeleteBookService
{
    public function __construct(private readonly BookRepository $books) {}

    public function execute(string $id): void
    {
        $book = $this->books->findActive($id);

        if ($book === null) {
            throw new ResourceNotFoundException('Libro');
        }

        if ($this->books->hasOpenLoans($id)) {
            throw new BookHasOpenLoansException;
        }

        $this->books->softDelete($id);
    }
}
