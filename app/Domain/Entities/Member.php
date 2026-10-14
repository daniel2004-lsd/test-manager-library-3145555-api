<?php

namespace App\Domain\Entities;

use App\Domain\Exceptions\ValidationException;
use App\Domain\Support\Uuid;

final class Member
{
    private function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly string $documentNumber,
    ) {}

    public static function create(string $fullName, string $documentNumber, ?string $id = null): self
    {
        $fullName = trim($fullName);
        $documentNumber = trim($documentNumber);

        if ($fullName === '' || $documentNumber === '') {
            throw new ValidationException('full_name', 'Nombre y documento son obligatorios.');
        }

        return new self($id ?? Uuid::v4(), $fullName, $documentNumber);
    }

    public static function reconstitute(string $id, string $fullName, string $documentNumber): self
    {
        return new self($id, $fullName, $documentNumber);
    }
}
