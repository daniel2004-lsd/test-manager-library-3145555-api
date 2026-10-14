<?php

namespace App\Domain\Exceptions;

class MemberLimitExceededException extends DomainException
{
    public function __construct(int $currentCount)
    {
        parent::__construct(
            "El socio ya tiene {$currentCount} libros sin devolver; el máximo es 3.",
            'MEMBER_LIMIT_EXCEEDED',
            422,
            [['field' => 'items', 'issue' => "límite de 3 libros sin devolver (actual: {$currentCount})"]],
        );
    }
}
