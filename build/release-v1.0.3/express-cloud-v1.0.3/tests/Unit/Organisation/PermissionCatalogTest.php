<?php

declare(strict_types=1);

namespace Tests\Unit\Organisation;

use App\Support\Authorization\PermissionCatalog;
use PHPUnit\Framework\TestCase;

final class PermissionCatalogTest extends TestCase
{
    public function test_permission_slugs_are_unique(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertSame(
            count($permissions),
            count(array_unique($permissions)),
        );
    }

    public function test_sensitive_administration_permissions_exist(): void
    {
        self::assertContains(
            'staff.access-key.reveal',
            PermissionCatalog::all(),
        );
        self::assertContains(
            'staff.sessions.revoke',
            PermissionCatalog::all(),
        );
        self::assertContains(
            'audit-log.view',
            PermissionCatalog::all(),
        );
    }
}
