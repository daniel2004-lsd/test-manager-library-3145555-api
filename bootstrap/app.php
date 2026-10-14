<?php

use App\Domain\Exceptions\DomainException;
use App\Domain\Exceptions\ValidationException as DomainValidationException;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\JwtAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::get('/health', [HealthController::class, 'show']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => JwtAuth::class,
            'role.admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => true);

        $exceptions->render(function (DomainValidationException $e): mixed {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'details' => $e->details,
            ], 422);
        });

        $exceptions->render(function (DomainException $e): mixed {
            return response()->json([
                'code' => $e->businessCode,
                'message' => $e->getMessage(),
                'details' => $e->details,
            ], $e->httpStatus);
        });

        $exceptions->render(function (ValidationException $e): mixed {
            $details = [];
            foreach ($e->errors() as $field => $messages) {
                $details[] = ['field' => (string) $field, 'issue' => (string) $messages[0]];
            }

            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => $details[0]['issue'] ?? 'Datos inválidos.',
                'details' => $details,
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e): mixed {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Recurso no encontrado.',
                'details' => [],
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e): mixed {
            return response()->json([
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Método no permitido para este recurso.',
                'details' => [],
            ], 405);
        });

        $exceptions->render(function (Throwable $e): mixed {
            return response()->json([
                'code' => 'INTERNAL_ERROR',
                'message' => 'Error interno del servidor.',
                'details' => [],
            ], 500);
        });
    })->create();
