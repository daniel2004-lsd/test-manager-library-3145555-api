<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\ItemAlreadyReturnedException;
use App\Domain\Exceptions\ResourceNotFoundException;

final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $loanId, string $itemId): array
    {
        $loan = $this->loans->find($loanId);

        if ($loan === null) {
            throw new ResourceNotFoundException('Préstamo');
        }

        $item = $loan->item($itemId);

        if ($item === null) {
            throw new ResourceNotFoundException('Línea del préstamo');
        }

        $now = $this->clock->now();

        if (! $this->loans->markReturned($loanId, $itemId, $now)) {
            throw new ItemAlreadyReturnedException;
        }

        $item->markReturned($now);

        return $item->toArray();
    }
}
