<?php

namespace App\Domain\Exceptions;

class UnauthenticatedException extends DomainException
{
    public function __construct(string $message = 'Token ausente, inválido o vencido.')
    {
        parent::__construct($message, 'UNAUTHENTICATED', 401);
    }
}
