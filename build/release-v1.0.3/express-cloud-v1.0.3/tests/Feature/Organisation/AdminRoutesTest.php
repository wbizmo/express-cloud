<?php

declare(strict_types=1);

namespace Tests\Feature\Organisation;

use App\Http\Middleware\RequirePermission;
use Tests\TestCase;

final class AdminRoutesTest extends TestCase
{
    public function test_admin_routes_require_authentication(): void
    {
        foreach ([
            '/admin/branches',
            '/admin/staff',
            '/admin/roles',
            '/admin/security/sessions',
        ] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_permission_middleware_alias_is_registered(): void
    {
        self::assertTrue(
            app('router')->getMiddleware()['permission']
                === RequirePermission::class,
        );
    }
}
