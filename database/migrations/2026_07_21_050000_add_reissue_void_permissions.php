<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<string, array{name: string, group: string, description: string}>
     */
    private const PERMISSIONS = [
        'sales.edit' => [
            'name' => 'Edit / reissue an invoice',
            'group' => 'Commercial',
            'description' => 'Edit or reissue an existing sale document.',
        ],
        'sales.void' => [
            'name' => 'Void a sale',
            'group' => 'Commercial',
            'description' => 'Void an eligible sale through the controlled workflow.',
        ],
        'activity.export' => [
            'name' => 'Export activity history',
            'group' => 'Operations',
            'description' => 'Export authorised activity and audit history.',
        ],
    ];

    /** @var list<string> */
    private const PRIVILEGED_ROLE_SLUGS = [
        'system-owner',
        'super-admin',
        'admin',
        'company-owner',
    ];

    public function up(): void
    {
        $now = now();
        $roleIds = DB::table('roles')
            ->whereIn('slug', self::PRIVILEGED_ROLE_SLUGS)
            ->pluck('id');

        foreach (self::PERMISSIONS as $slug => $definition) {
            $permissionId = DB::table('permissions')
                ->where('slug', $slug)
                ->value('id');

            if (! is_string($permissionId) || $permissionId === '') {
                $permissionId = Str::ulid()->toString();

                DB::table('permissions')->insert([
                    'id' => $permissionId,
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'group' => $definition['group'],
                    'description' => $definition['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('permissions')
                    ->where('id', $permissionId)
                    ->update([
                        'name' => $definition['name'],
                        'group' => $definition['group'],
                        'description' => $definition['description'],
                        'updated_at' => $now,
                    ]);
            }

            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', array_keys(self::PERMISSIONS))
            ->pluck('id');

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('slug', array_keys(self::PERMISSIONS))
            ->delete();
    }
};
