<?php

namespace App\Domain\Exceptions;

class MemberHasOverdueException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'El socio tiene libros vencidos y no puede solicitar otro préstamo.',
            'MEMBER_HAS_OVERDUE',
            409,
        );
    }
}
