<?php

namespace App\Application\Ports;

use App\Domain\Entities\Member;

interface MemberRepository
{
    public function find(string $id): ?Member;

    public function findByDocument(string $documentNumber): ?Member;

    /** @return array{items: list<Member>, total: int} */
    public function search(?string $q, int $page, int $size): array;

    public function insert(Member $member): void;

    public function activeLoanCount(string $memberId): int;
}
