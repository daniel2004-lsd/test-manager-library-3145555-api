<?php

namespace App\Application\Services;

use App\Application\Ports\MemberRepository;
use App\Domain\Exceptions\ResourceNotFoundException;

final class GetMemberService
{
    public function __construct(private readonly MemberRepository $members) {}

    /** @return array{id: string, full_name: string, document_number: string, active_loans: int} */
    public function execute(string $id): array
    {
        $member = $this->members->find($id);

        if ($member === null) {
            throw new ResourceNotFoundException('Socio');
        }

        return [
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber,
            'active_loans' => $this->members->activeLoanCount($member->id),
        ];
    }
}
