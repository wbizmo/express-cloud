<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class LiveSessionsRoutesTest extends TestCase
{
    public function test_live_sessions_require_authentication(): void
    {
        $this->get('/admin/security/sessions')->assertRedirect('/');
    }

    public function test_live_session_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains(
            'security.sessions.terminate',
            $permissions,
        );
    }
}
