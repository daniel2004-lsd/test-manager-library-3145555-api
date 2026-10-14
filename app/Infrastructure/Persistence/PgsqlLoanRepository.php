<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\LoanRepository;
use App\Application\Support\Iso8601;
use App\Domain\Entities\Loan;
use App\Domain\Entities\LoanItem;
use App\Domain\Exceptions\BookNotAvailableException;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgsqlLoanRepository implements LoanRepository
{
    public function registerLoan(Loan $loan): void
    {
        DB::transaction(function () use ($loan): void {
            foreach ($loan->items as $item) {
                $available = DB::table('book')
                    ->where('id', $item->bookId)
                    ->whereNull('deleted_at')
                    ->where('available_copies', '>', 0)
                    ->update(['available_copies' => DB::raw('available_copies - 1')]);

                if ($available !== 1) {
                    throw new BookNotAvailableException($item->bookTitle);
                }
            }

            DB::table('loan')->insert([
                'id' => $loan->id,
                'member_id' => $loan->memberId,
                'loaned_by_user_id' => $loan->loanedByUserId,
                'loaned_at' => $loan->loanedAt->format('Y-m-d\TH:i:s.u\Z'),
            ]);

            foreach ($loan->items as $item) {
                DB::table('loan_item')->insert([
                    'id' => $item->id,
                    'loan_id' => $loan->id,
                    'book_id' => $item->bookId,
                    'book_title' => $item->bookTitle,
                    'isbn' => $item->isbn,
                    'category_name' => $item->categoryName,
                    'due_at' => $item->dueAt->format('Y-m-d\TH:i:s.u\Z'),
                ]);
            }
        });
    }

    public function find(string $id): ?Loan
    {
        $row = DB::table('loan')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        $items = DB::table('loan_item')->where('loan_id', $id)->orderBy('id')->get()
            ->map(static fn (object $item): LoanItem => LoanItem::reconstitute(
                (string) $item->id,
                (string) $item->loan_id,
                (string) $item->book_id,
                (string) $item->book_title,
                (string) $item->isbn,
                (string) $item->category_name,
                Iso8601::parseDb((string) $item->due_at),
                Iso8601::parseDb($item->returned_at === null ? null : (string) $item->returned_at),
            ))
            ->all();

        return Loan::reconstitute(
            (string) $row->id,
            (string) $row->member_id,
            (string) $row->loaned_by_user_id,
            Iso8601::parseDb((string) $row->loaned_at),
            $items,
        );
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?string $status,
        ?string $memberId,
        int $page,
        int $size,
    ): array {
        $statusExpression = "CASE
            WHEN COUNT(li.id) FILTER (WHERE li.returned_at IS NULL) = 0 THEN 'closed'
            WHEN COUNT(li.id) FILTER (WHERE li.returned_at IS NULL AND li.due_at < now()) > 0 THEN 'overdue'
            ELSE 'open'
        END";

        $base = DB::table('loan as l')
            ->join('loan_item as li', 'li.loan_id', '=', 'l.id')
            ->groupBy('l.id', 'l.member_id', 'l.loaned_at')
            ->selectRaw("l.id as id, l.member_id as member_id, l.loaned_at as loaned_at, {$statusExpression} as status")
            ->when($from !== null, fn ($query) => $query->where('l.loaned_at', '>=', $from->format('Y-m-d\TH:i:s\Z')))
            ->when($to !== null, fn ($query) => $query->where('l.loaned_at', '<', $to->modify('+1 day')->format('Y-m-d\TH:i:s\Z')))
            ->when($memberId !== null && $memberId !== '', fn ($query) => $query->where('l.member_id', $memberId));

        $query = DB::query()->fromSub($base, 'loans')
            ->when($status !== null && $status !== '', fn ($builder) => $builder->where('status', $status));

        $total = (clone $query)->count();
        $items = $query->orderByDesc('loaned_at')->orderByDesc('id')->forPage($page, $size)->get()
            ->map(static fn (object $row): array => [
                'id' => (string) $row->id,
                'member_id' => (string) $row->member_id,
                'loaned_at' => (string) $row->loaned_at,
                'status' => (string) $row->status,
            ])
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function overdueItems(): array
    {
        return DB::table('loan_item as li')
            ->join('loan as l', 'l.id', '=', 'li.loan_id')
            ->join('member as m', 'm.id', '=', 'l.member_id')
            ->whereNull('li.returned_at')
            ->whereColumn('li.due_at', '<', DB::raw('now()'))
            ->selectRaw('li.loan_id as loan_id, li.id as item_id, li.book_title as book_title,
                m.full_name as member_name, li.due_at as due_at,
                ceil(extract(epoch from (now() - li.due_at)) / 86400)::int as days_late')
            ->orderByDesc('days_late')
            ->orderBy('li.due_at')
            ->get()
            ->map(static fn (object $row): array => [
                'loan_id' => (string) $row->loan_id,
                'item_id' => (string) $row->item_id,
                'book_title' => (string) $row->book_title,
                'member_name' => (string) $row->member_name,
                'due_at' => (string) $row->due_at,
                'days_late' => (int) $row->days_late,
            ])
            ->all();
    }

    public function markReturned(string $loanId, string $itemId, DateTimeImmutable $now): bool
    {
        return DB::transaction(function () use ($loanId, $itemId, $now): bool {
            $item = DB::table('loan_item')
                ->where('id', $itemId)
                ->where('loan_id', $loanId)
                ->first();

            if ($item === null || $item->returned_at !== null) {
                return false;
            }

            $affected = DB::table('loan_item')
                ->where('id', $itemId)
                ->where('loan_id', $loanId)
                ->whereNull('returned_at')
                ->update(['returned_at' => $now->format('Y-m-d\TH:i:s.u\Z')]);

            if ($affected !== 1) {
                return false;
            }

            DB::table('book')
                ->where('id', (string) $item->book_id)
                ->update(['available_copies' => DB::raw('available_copies + 1')]);

            return true;
        });
    }

    public function countActiveItemsForMember(string $memberId): int
    {
        return (int) DB::table('loan_item')
            ->join('loan', 'loan.id', '=', 'loan_item.loan_id')
            ->where('loan.member_id', $memberId)
            ->whereNull('loan_item.returned_at')
            ->count();
    }

    public function hasOverdueForMember(string $memberId): bool
    {
        return DB::table('loan_item')
            ->join('loan', 'loan.id', '=', 'loan_item.loan_id')
            ->where('loan.member_id', $memberId)
            ->whereNull('loan_item.returned_at')
            ->whereColumn('loan_item.due_at', '<', DB::raw('now()'))
            ->exists();
    }
}
