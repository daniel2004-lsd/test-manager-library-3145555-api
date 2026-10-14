<?php

namespace App\Application\Services;

use App\Application\Ports\MemberRepository;

final class SearchMembersService
{
    public function __construct(private readonly MemberRepository $members) {}

    /** @return array{items: list<array{id: string, full_name: string, document_number: string}>, page: int, size: int, total: int} */
    public function execute(?string $q, int $page, int $size): array
    {
        $result = $this->members->search($q, $page, $size);

        return [
            'items' => array_map(
                static fn ($member): array => [
                    'id' => $member->id,
                    'full_name' => $member->fullName,
                    'document_number' => $member->documentNumber,
                ],
                $result['items'],
            ),
            'page' => $page,
            'size' => $size,
            'total' => $result['total'],
        ];
    }
}
