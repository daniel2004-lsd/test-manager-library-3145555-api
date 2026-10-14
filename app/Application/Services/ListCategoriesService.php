<?php

namespace App\Application\Services;

use App\Application\Ports\CategoryRepository;

final class ListCategoriesService
{
    public function __construct(private readonly CategoryRepository $categories) {}

    /** @return list<array{id: string, name: string}> */
    public function execute(): array
    {
        return array_map(
            static fn ($category): array => $category->toArray(),
            $this->categories->all(),
        );
    }
}
