<?php

namespace App\Http\Middleware;

use App\Domain\Exceptions\ForbiddenRoleException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('auth_role') !== 'admin') {
            throw new ForbiddenRoleException;
        }

        return $next($request);
    }
}
