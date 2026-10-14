<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Exceptions\ResourceNotFoundException;

final class UploadCoverService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $covers,
        private readonly GetBookService $presenter,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $bookId, string $tmpPath, int $sizeBytes): array
    {
        $book = $this->books->findActive($bookId);

        if ($book === null) {
            throw new ResourceNotFoundException('Libro');
        }

        $previousKey = $book->coverKey;
        $key = $this->covers->store($tmpPath, $sizeBytes);

        $this->books->setCoverKey($bookId, $key);
        $this->covers->delete($previousKey);

        return $this->presenter->execute($bookId);
    }
}
