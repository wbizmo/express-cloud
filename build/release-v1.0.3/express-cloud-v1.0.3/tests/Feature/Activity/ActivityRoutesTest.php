<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class ActivityRoutesTest extends TestCase
{
    public function test_activity_routes_require_authentication(): void
    {
        $this->get('/admin/activity')->assertRedirect('/');
        $this->get('/admin/products/01TEST/activity')->assertRedirect('/');
    }

    public function test_activity_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('activity.view', $permissions);
        self::assertContains('activity.products.view', $permissions);
    }
}
