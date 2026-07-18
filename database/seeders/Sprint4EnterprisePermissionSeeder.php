<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\Sprint4Permissions;
use Illuminate\Database\Seeder;

final class Sprint4EnterprisePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Sprint4Permissions::grouped() as $group => $slugs) {
            foreach ($slugs as $slug) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => str($slug)->replace('.', ' ')->title()->toString(),
                        'description' => str($slug)->replace('.', ' ')->title()->toString(),
                        'group' => $group,
                    ],
                );
            }
        }

        $grant = static function (string $roleName, array $slugs): void {
            $role = Role::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($roleName)])
                ->first();

            if ($role === null) {
                return;
            }

            $role->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('slug', $slugs)->pluck('id')->all(),
            );
        };

        foreach (['System Owner', 'Super Admin', 'Admin', 'Company Owner'] as $roleName) {
            $grant($roleName, Sprint4Permissions::all());
        }

        $grant(
            'Branch Manager',
            array_values(array_diff(
                Sprint4Permissions::all(),
                ['activity.view.all-branches', 'lisa.audit.view'],
            )),
        );

        $inventory = Role::query()->firstOrCreate(
            ['name' => 'Inventory Staff'],
            ['description' => 'Branch-scoped inventory and purchasing access.'],
        );

        $inventory->permissions()->syncWithoutDetaching(
            Permission::query()
                ->whereIn('slug', [
                    'inventory.view',
                    'inventory.transfer',
                    'inventory.intake',
                    'products.view',
                    'products.prices.adjust',
                    'suppliers.view',
                    'procurement.view',
                    'procurement.create',
                    'procurement.receive',
                    'purchases.view',
                    'purchases.create',
                    'categories.manage',
                    'branches.view',
                ])
                ->pluck('id')
                ->all(),
        );
    }
}
