<?php

namespace App\Domain\Exceptions;

class InvalidDateRangeException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'El rango de fechas es inválido: «from» debe ser anterior o igual a «to».',
            'INVALID_DATE_RANGE',
            422,
            [['field' => 'from', 'issue' => 'rango invertido']],
        );
    }
}
