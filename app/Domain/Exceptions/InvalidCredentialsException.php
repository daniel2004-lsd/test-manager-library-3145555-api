<?php

namespace App\Domain\Exceptions;

class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Usuario o contraseña incorrectos.', 'INVALID_CREDENTIALS', 401);
    }
}
