<?php

namespace App\Domain\Exceptions;

class DocumentTakenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese número de documento ya está registrado.', 'DOCUMENT_TAKEN', 409);
    }
}
