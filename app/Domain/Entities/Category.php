<?php

namespace App\Domain\Entities;

final class Category
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
