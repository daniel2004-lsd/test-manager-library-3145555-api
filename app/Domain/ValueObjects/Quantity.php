<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationException;

final class Quantity
{
    private function __construct(public readonly int $value) {}

    public static function total(int $value): self
    {
        if ($value < 1) {
            throw new ValidationException('total_copies', 'El total de ejemplares debe ser al menos 1.');
        }

        return new self($value);
    }

    public static function available(int $value, int $total): self
    {
        if ($value < 0 || $value > $total) {
            throw new ValidationException(
                'available_copies',
                'Los ejemplares disponibles deben estar entre 0 y el total.',
            );
        }

        return new self($value);
    }
}
