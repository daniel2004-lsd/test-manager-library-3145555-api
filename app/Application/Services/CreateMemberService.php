<?php

namespace App\Application\Services;

use App\Application\Ports\MemberRepository;
use App\Domain\Entities\Member;
use App\Domain\Exceptions\DocumentTakenException;

final class CreateMemberService
{
    public function __construct(private readonly MemberRepository $members) {}

    /** @return array{id: string, full_name: string, document_number: string} */
    public function execute(string $fullName, string $documentNumber): array
    {
        $member = Member::create($fullName, $documentNumber);

        if ($this->members->findByDocument($member->documentNumber) !== null) {
            throw new DocumentTakenException;
        }

        $this->members->insert($member);

        return [
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber,
        ];
    }
}
