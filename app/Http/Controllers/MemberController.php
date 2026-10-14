<?php

namespace App\Http\Controllers;

use App\Application\Services\CreateMemberService;
use App\Application\Services\GetMemberService;
use App\Application\Services\SearchMembersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MemberController extends Controller
{
    public function __construct(
        private readonly SearchMembersService $search,
        private readonly GetMemberService $get,
        private readonly CreateMemberService $create,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->search->execute(
            $data['q'] ?? null,
            (int) ($data['page'] ?? 1),
            (int) ($data['size'] ?? 20),
        ));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->get->execute($id));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ]);

        return response()->json($this->create->execute($data['full_name'], $data['document_number']), 201);
    }
}
