<?php

namespace App\Http\Controllers;

use App\Application\Services\CreateLibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(private readonly CreateLibrarianService $createLibrarian) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'role' => ['required', 'string', 'max:40'],
        ]);

        return response()->json(
            $this->createLibrarian->execute($data['username'], $data['password'], $data['role']),
            201,
        );
    }
}
