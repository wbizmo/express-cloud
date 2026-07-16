<?php

declare(strict_types=1);

namespace Tests\Feature\Backup;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class BackupRoutesTest extends TestCase
{
    public function test_backup_screen_requires_authentication(): void
    {
        $this->get('/admin/operations/backups')
            ->assertRedirect('/');
    }

    public function test_backup_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('backups.view', $permissions);
        self::assertContains('backups.create', $permissions);
        self::assertContains('backups.verify', $permissions);
    }
}
