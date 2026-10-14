<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\Entities\Loan;
use App\Domain\Exceptions\MemberHasOverdueException;
use App\Domain\Exceptions\MemberLimitExceededException;
use App\Domain\Exceptions\ResourceNotFoundException;
use App\Domain\Exceptions\ValidationException;
use DateTimeImmutable;

final class LendBooksService
{
    public const int MAX_ACTIVE_PER_MEMBER = 3;

    public function __construct(
        private readonly MemberRepository $members,
        private readonly BookRepository $books,
        private readonly LoanRepository $loans,
        private readonly CategoryRepository $categories,
        private readonly Clock $clock,
    ) {}

    /**
     * @param  list<string>  $bookIds
     * @return array<string, mixed>
     */
    public function execute(
        string $memberId,
        array $bookIds,
        string $loanedByUserId,
        ?DateTimeImmutable $loanedAt = null,
    ): array {
        if (count($bookIds) < 1 || count($bookIds) > 3) {
            throw new ValidationException('items', 'Un préstamo debe tener entre 1 y 3 libros.');
        }

        if ($this->members->find($memberId) === null) {
            throw new ResourceNotFoundException('Socio');
        }

        $now = $this->clock->now();

        if ($loanedAt !== null && $loanedAt > $now) {
            throw new ValidationException('loaned_at', 'La fecha del préstamo no puede ser futura.');
        }

        if ($this->loans->hasOverdueForMember($memberId)) {
            throw new MemberHasOverdueException;
        }

        $active = $this->loans->countActiveItemsForMember($memberId);

        if ($active + count($bookIds) > self::MAX_ACTIVE_PER_MEMBER) {
            throw new MemberLimitExceededException($active);
        }

        $frozen = [];
        foreach ($bookIds as $bookId) {
            $book = $this->books->findActive($bookId);

            if ($book === null) {
                throw new ResourceNotFoundException('Libro');
            }

            $frozen[] = [
                'book_id' => $book->id,
                'book_title' => $book->title,
                'isbn' => $book->isbn->value,
                'category_name' => $this->categoryName($book->categoryId),
            ];
        }

        $loan = Loan::register($memberId, $loanedByUserId, $loanedAt ?? $now, $frozen);

        $this->loans->registerLoan($loan);

        return $loan->toArray($now);
    }

    private function categoryName(string $categoryId): string
    {
        foreach ($this->categories->all() as $category) {
            if ($category->id === $categoryId) {
                return $category->name;
            }
        }

        return '';
    }
}
