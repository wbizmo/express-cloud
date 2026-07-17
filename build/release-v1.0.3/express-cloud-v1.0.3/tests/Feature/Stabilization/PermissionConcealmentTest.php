<?php

declare(strict_types=1);

namespace Tests\Feature\Stabilization;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PermissionConcealmentTest extends TestCase
{
    #[Test]
    public function denied_permissions_are_concealed_as_not_found(): void
    {
        $middleware = file_get_contents(
            app_path('Http/Middleware/RequirePermission.php'),
        );

        self::assertIsString($middleware);
        self::assertStringContainsString(
            'hasPermission',
            $middleware,
        );
        self::assertMatchesRegularExpression(
            '/abort(?:_unless)?\s*\([^;]*404/s',
            $middleware,
            'Permission denial must be concealed with an HTTP 404 response.',
        );
        self::assertStringNotContainsString(
            'abort(403',
            $middleware,
        );
    }
}
