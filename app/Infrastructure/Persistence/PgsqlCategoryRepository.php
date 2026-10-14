<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\CategoryRepository;
use App\Domain\Entities\Category;
use Illuminate\Support\Facades\DB;

final class PgsqlCategoryRepository implements CategoryRepository
{
    public function all(): array
    {
        return DB::table('category')
            ->orderBy('name')
            ->get()
            ->map(static fn ($row): Category => new Category((string) $row->id, (string) $row->name))
            ->all();
    }
}
