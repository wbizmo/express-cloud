<?php

use App\Http\Middleware\EnforceSessionInactivity;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\RequirePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'session.inactivity' => EnforceSessionInactivity::class,
            'permission' => RequirePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(
            static function (
                ValidationException $exception,
                Request $request,
            ): ?JsonResponse {
                if (! $request->expectsJson()) {
                    return null;
                }

                return response()->json([
                    'error_code' => 'VALIDATION_FAILED',
                    'message' => 'Some submitted values need attention.',
                    'errors' => $exception->errors(),
                ], 422);
            },
        );

        $exceptions->render(
            static function (
                DomainException $exception,
                Request $request,
            ): ?JsonResponse {
                if (! $request->expectsJson()) {
                    return null;
                }

                return response()->json([
                    'error_code' => 'BUSINESS_RULE_FAILED',
                    'message' => $exception->getMessage(),
                ], 409);
            },
        );

        $exceptions->render(
            static function (
                Throwable $exception,
                Request $request,
            ): ?JsonResponse {
                if (
                    ! $request->expectsJson()
                    || config('app.debug')
                ) {
                    return null;
                }

                return response()->json([
                    'error_code' => 'UNEXPECTED_ERROR',
                    'message' => 'Something went wrong. Please try again.',
                ], 500);
            },
        );
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
