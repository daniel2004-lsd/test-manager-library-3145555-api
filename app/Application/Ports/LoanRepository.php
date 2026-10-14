<?php

namespace App\Application\Ports;

use App\Domain\Entities\Loan;
use DateTimeImmutable;

interface LoanRepository
{
    /** Una sola transacción: descuento condicional por línea + insert de préstamo y líneas. */
    public function registerLoan(Loan $loan): void;

    public function find(string $id): ?Loan;

    /** @return array{items: list<array{id: string, member_id: string, loaned_at: string, status: string}>, total: int} */
    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?string $status,
        ?string $memberId,
        int $page,
        int $size,
    ): array;

    /** @return list<array{loan_id: string, item_id: string, book_title: string, member_name: string, due_at: string, days_late: int}> */
    public function overdueItems(): array;

    public function markReturned(string $loanId, string $itemId, DateTimeImmutable $now): bool;

    public function countActiveItemsForMember(string $memberId): int;

    public function hasOverdueForMember(string $memberId): bool;
}
