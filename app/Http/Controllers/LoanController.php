<?php

namespace App\Http\Controllers;

use App\Application\Services\GetLoanService;
use App\Application\Services\LendBooksService;
use App\Application\Services\ListLoansService;
use App\Application\Services\OverdueLoansService;
use App\Application\Services\ReturnItemService;
use App\Application\Support\Iso8601;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoanController extends Controller
{
    public function __construct(
        private readonly LendBooksService $lend,
        private readonly ListLoansService $list,
        private readonly GetLoanService $get,
        private readonly ReturnItemService $returnItem,
        private readonly OverdueLoansService $overdue,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.book_id' => ['required', 'uuid'],
            'loaned_at' => ['nullable', 'date'],
        ]);

        $bookIds = array_values(array_map(
            static fn (array $item): string => $item['book_id'],
            $data['items'],
        ));

        $loanedAt = isset($data['loaned_at']) ? Iso8601::parseInput($data['loaned_at']) : null;

        return response()->json(
            $this->lend->execute(
                $data['member_id'],
                $bookIds,
                (string) $request->attributes->get('auth_user_id'),
                $loanedAt,
            ),
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:20'],
            'member_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->list->execute(
            $data['from'] ?? null,
            $data['to'] ?? null,
            $data['status'] ?? null,
            $data['member_id'] ?? null,
            (int) ($data['page'] ?? 1),
            (int) ($data['size'] ?? 20),
        ));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->get->execute($id));
    }

    public function returnItem(string $loanId, string $itemId): JsonResponse
    {
        return response()->json($this->returnItem->execute($loanId, $itemId));
    }

    public function overdue(): JsonResponse
    {
        return response()->json($this->overdue->execute());
    }
}
