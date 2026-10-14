<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\MemberRepository;
use App\Domain\Entities\Member;
use Illuminate\Support\Facades\DB;

final class PgsqlMemberRepository implements MemberRepository
{
    public function find(string $id): ?Member
    {
        $row = DB::table('member')->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function findByDocument(string $documentNumber): ?Member
    {
        $row = DB::table('member')->where('document_number', $documentNumber)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function search(?string $q, int $page, int $size): array
    {
        $query = DB::table('member');

        if ($q !== null && $q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('full_name', 'ilike', $like)->orWhere('document_number', 'ilike', $like);
            });
        }

        $total = (clone $query)->count();
        $items = $query->orderBy('full_name')->orderBy('id')->forPage($page, $size)->get()
            ->map(fn ($row): Member => $this->hydrate($row))
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function insert(Member $member): void
    {
        DB::table('member')->insert([
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber,
        ]);
    }

    public function activeLoanCount(string $memberId): int
    {
        return (int) DB::table('loan_item')
            ->join('loan', 'loan.id', '=', 'loan_item.loan_id')
            ->where('loan.member_id', $memberId)
            ->whereNull('loan_item.returned_at')
            ->count();
    }

    private function hydrate(object $row): Member
    {
        return Member::reconstitute((string) $row->id, (string) $row->full_name, (string) $row->document_number);
    }
}
