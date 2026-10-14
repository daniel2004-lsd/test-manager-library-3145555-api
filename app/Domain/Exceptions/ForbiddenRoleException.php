<?php

namespace App\Domain\Exceptions;

class ForbiddenRoleException extends DomainException
{
    public function __construct()
    {
        parent::__construct('No tiene permisos para realizar esta operación.', 'FORBIDDEN_ROLE', 403);
    }
}
