<?php

namespace App\Domain\Exceptions;

class DuplicateBookInLoanException extends DomainException
{
    public function __construct(string $bookId)
    {
        parent::__construct(
            'El mismo libro no puede repetirse dentro de un préstamo.',
            'DUPLICATE_BOOK_IN_LOAN',
            422,
            [['field' => 'items', 'issue' => "libro repetido: {$bookId}"]],
        );
    }
}
