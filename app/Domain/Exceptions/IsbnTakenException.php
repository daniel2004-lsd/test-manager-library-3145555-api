<?php

namespace App\Domain\Exceptions;

class IsbnTakenException extends DomainException
{
    public function __construct(string $isbn)
    {
        parent::__construct("Ya existe un libro activo con el ISBN {$isbn}.", 'ISBN_TAKEN', 409, [
            ['field' => 'isbn', 'issue' => 'ISBN ya registrado entre libros activos'],
        ]);
    }
}
