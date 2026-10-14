<?php

namespace App\Http\Controllers;

use App\Application\Services\MostBorrowedReportService;
use App\Application\Support\Iso8601;
use App\Domain\ValueObjects\DateRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReportController extends Controller
{
    public function __construct(private readonly MostBorrowedReportService $report) {}

    public function mostBorrowed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
        ]);

        $range = DateRange::of(
            Iso8601::parseInput($data['from']),
            Iso8601::parseInput($data['to']),
        );

        return response()->json($this->report->execute($range));
    }
}
