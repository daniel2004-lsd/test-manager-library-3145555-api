<?php

namespace App\Http\Middleware;

use App\Application\Ports\TokenIssuer;
use App\Domain\Exceptions\UnauthenticatedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class JwtAuth
{
    public function __construct(private readonly TokenIssuer $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(\S+)$/', $header, $matches) !== 1) {
            throw new UnauthenticatedException('Falta el encabezado Authorization con el token.');
        }

        $claims = $this->tokens->parse($matches[1]);

        $request->attributes->set('auth_user_id', $claims['sub']);
        $request->attributes->set('auth_role', $claims['role']);

        return $next($request);
    }
}
