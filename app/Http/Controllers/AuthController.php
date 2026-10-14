<?php

namespace App\Http\Controllers;

use App\Application\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly LoginService $login) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        return response()->json($this->login->execute($data['username'], $data['password']));
    }
}
