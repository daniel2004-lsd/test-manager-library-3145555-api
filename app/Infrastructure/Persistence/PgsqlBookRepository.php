<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\BookRepository;
use App\Domain\Entities\Book;
use App\Domain\Exceptions\ValidationException;
use Illuminate\Support\Facades\DB;

final class PgsqlBookRepository implements BookRepository
{
    public function findActive(string $id): ?Book
    {
        $row = DB::table('book')->where('id', $id)->whereNull('deleted_at')->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByIsbnActive(string $isbn): ?Book
    {
        $row = DB::table('book')->where('isbn', $isbn)->whereNull('deleted_at')->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function findManyActive(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $books = [];
        foreach (DB::table('book')->whereIn('id', $ids)->whereNull('deleted_at')->get() as $row) {
            $book = $this->hydrate($row);
            $books[$book->id] = $book;
        }

        return $books;
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): array
    {
        $query = DB::table('book')->whereNull('deleted_at');

        if ($q !== null && $q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('title', 'ilike', $like)->orWhere('author', 'ilike', $like);
            });
        }

        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('title')->orderBy('id')->forPage($page, $size)->get()
            ->map(fn ($row): Book => $this->hydrate($row))
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function insert(Book $book): void
    {
        DB::table('book')->insert([
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn->value,
            'category_id' => $book->categoryId,
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
        ]);
    }

    public function update(string $id, string $title, string $author, string $categoryId, int $newTotal): void
    {
        $current = DB::table('book')->where('id', $id)->whereNull('deleted_at')->first();

        if ($current === null) {
            return;
        }

        $delta = $newTotal - (int) $current->total_copies;

        $affected = DB::table('book')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->whereRaw('(available_copies + ?) >= 0', [$delta])
            ->update([
                'title' => trim($title),
                'author' => trim($author),
                'category_id' => $categoryId,
                'total_copies' => $newTotal,
                'available_copies' => DB::raw('(available_copies + '.(int) $delta.')'),
            ]);

        if ($affected === 0) {
            throw new ValidationException(
                'total_copies',
                'El nuevo total dejaría los ejemplares disponibles en negativo.',
            );
        }
    }

    public function decrementAvailable(string $id): bool
    {
        return DB::table('book')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->where('available_copies', '>', 0)
            ->update(['available_copies' => DB::raw('available_copies - 1')]) === 1;
    }

    public function incrementAvailable(string $id): void
    {
        DB::table('book')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['available_copies' => DB::raw('available_copies + 1')]);
    }

    public function hasOpenLoans(string $id): bool
    {
        return DB::table('loan_item')
            ->where('book_id', $id)
            ->whereNull('returned_at')
            ->exists();
    }

    public function softDelete(string $id): void
    {
        DB::table('book')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()->format('Y-m-d\TH:i:s.u\Z')]);
    }

    public function setCoverKey(string $id, ?string $coverKey): void
    {
        DB::table('book')->where('id', $id)->update(['cover_key' => $coverKey]);
    }

    private function hydrate(object $row): Book
    {
        return Book::reconstitute(
            (string) $row->id,
            (string) $row->title,
            (string) $row->author,
            (string) $row->isbn,
            (string) $row->category_id,
            (int) $row->total_copies,
            (int) $row->available_copies,
            $row->cover_key === null ? null : (string) $row->cover_key,
            null,
        );
    }
}
