<?php

namespace App\Domain\Exceptions;

class BookHasOpenLoansException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'El libro tiene préstamos abiertos y no puede darse de baja.',
            'BOOK_HAS_OPEN_LOANS',
            409,
        );
    }
}
