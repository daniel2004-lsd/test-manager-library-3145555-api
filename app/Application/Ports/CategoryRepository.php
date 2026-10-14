<?php

namespace App\Application\Ports;

use App\Domain\Entities\Category;

interface CategoryRepository
{
    /** @return list<Category> */
    public function all(): array;
}
