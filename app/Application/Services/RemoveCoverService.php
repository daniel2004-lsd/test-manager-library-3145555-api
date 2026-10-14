<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Exceptions\ResourceNotFoundException;

final class RemoveCoverService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $covers,
        private readonly GetBookService $presenter,
    ) {}

    /** La clave se anula en la transacción; el binario se borra después del commit. */
    /** @return array<string, mixed> */
    public function execute(string $bookId): array
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFoundException('Libro');
        }

        $previousKey = $book->coverKey;
        $this->books->setCoverKey($bookId, null);
        $this->covers->delete($previousKey);

        return $this->presenter->execute($bookId);
    }
}
