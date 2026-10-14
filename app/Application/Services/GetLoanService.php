<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\ResourceNotFoundException;

final class GetLoanService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $id): array
    {
        $loan = $this->loans->find($id);

        if ($loan === null) {
            throw new ResourceNotFoundException('Préstamo');
        }

        return $loan->toArray($this->clock->now());
    }
}
