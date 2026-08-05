<?php

use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\EnforceBranchScope;
use App\Http\Middleware\EnforceSessionInactivity;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureSaleVisibility;
use App\Http\Middleware\RequireAnyPermission;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\VerifyOperationsCronHmac;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [ApplySecurityHeaders::class]);

        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'branch.scope' => EnforceBranchScope::class,
            'session.inactivity' => EnforceSessionInactivity::class,
            'permission' => RequirePermission::class,
            'permission.any' => RequireAnyPermission::class,
            'sale.visible' => EnsureSaleVisibility::class,
            'operations.cron.hmac' => VerifyOperationsCronHmac::class,
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
    })->create();
