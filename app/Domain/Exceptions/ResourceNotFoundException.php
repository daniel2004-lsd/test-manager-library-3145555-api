<?php

namespace App\Domain\Exceptions;

class ResourceNotFoundException extends DomainException
{
    public function __construct(string $resource = 'Recurso')
    {
        parent::__construct("{$resource} no encontrado.", 'NOT_FOUND', 404);
    }
}
