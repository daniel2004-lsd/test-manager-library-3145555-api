<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException;

final class Isbn
{
    private function __construct(public readonly string $value)
    {
        if (! preg_match('/^\d{13}$/', $value)) {
            throw new ValidationException('isbn', 'El ISBN debe tener exactamente 13 dígitos.');
        }
    }

    public static function of(string $value): self
    {
        return new self(trim($value));
    }
}
