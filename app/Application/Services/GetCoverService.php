<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Exceptions\ResourceNotFoundException;

final class GetCoverService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $covers,
    ) {}

    /** @return array{path: string, mime: string} */
    public function execute(string $bookId): array
    {
        $book = $this->books->findActive($bookId);

        if ($book === null || $book->coverKey === null) {
            throw new ResourceNotFoundException('Portada');
        }

        $file = $this->covers->read($book->coverKey);

        if ($file === null) {
            throw new ResourceNotFoundException('Portada');
        }

        return $file;
    }
}
