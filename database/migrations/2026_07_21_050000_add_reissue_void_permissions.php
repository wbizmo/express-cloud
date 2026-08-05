<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $newPermissions = [
            'sales.edit',
            'sales.void',
            'activity.export',
        ];

        foreach ($newPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::where('name', 'system-owner')->first();
        if ($role) {
            $role->givePermissionTo($newPermissions);
        }

        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $role->givePermissionTo($newPermissions);
        }

        $role = Role::where('name', 'company-owner')->first();
        if ($role) {
            $role->givePermissionTo($newPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['sales.edit', 'sales.void', 'activity.export'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
