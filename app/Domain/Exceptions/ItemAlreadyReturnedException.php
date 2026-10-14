<?php

namespace App\Domain\Exceptions;

class ItemAlreadyReturnedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'La línea del préstamo ya fue devuelta.',
            'ITEM_ALREADY_RETURNED',
            409,
        );
    }
}
