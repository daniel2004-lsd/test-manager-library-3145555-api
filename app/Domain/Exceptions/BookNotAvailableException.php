<?php

namespace App\Domain\Exceptions;

class BookNotAvailableException extends DomainException
{
    public function __construct(string $bookTitle)
    {
        parent::__construct(
            "No quedan ejemplares disponibles de «{$bookTitle}».",
            'BOOK_NOT_AVAILABLE',
            409,
            [['field' => 'items', 'issue' => 'sin ejemplares disponibles']],
        );
    }
}
