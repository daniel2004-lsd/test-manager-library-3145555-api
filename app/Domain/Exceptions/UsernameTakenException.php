<?php

namespace App\Domain\Exceptions;

class UsernameTakenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese nombre de usuario ya está en uso.', 'USERNAME_TAKEN', 409);
    }
}
