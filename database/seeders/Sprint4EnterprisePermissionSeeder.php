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
        foreach (Sprint4Permissions::grouped() as $group => $names) {
            foreach ($names as $name) {
                Permission::query()->firstOrCreate(['name' => $name], ['label' => str($name)->replace('.', ' ')->title(), 'group' => $group]);
            }
        }
        $grant = function ($roleName, $names) {
            $r = Role::query()->whereRaw('LOWER(name)=?', [mb_strtolower($roleName)])->first();
            if ($r) {
                $r->permissions()->syncWithoutDetaching(Permission::query()->whereIn('name', $names)->pluck('id'));
            }
        };
        foreach (['Super Admin', 'Admin', 'Company Owner'] as $r) {
            $grant($r, Sprint4Permissions::all());
        }
        $grant('Branch Manager', array_values(array_diff(Sprint4Permissions::all(), ['activity.view.all-branches', 'lisa.audit.view'])));
        $inventory = Role::query()->firstOrCreate(['name' => 'Inventory Staff'], ['description' => 'Branch-scoped stock transfer and purchasing. Product creation remains separately assignable.']);
        $inventory->permissions()->syncWithoutDetaching(Permission::query()->whereIn('name', ['inventory.view', 'inventory.transfer', 'inventory.intake', 'products.view', 'suppliers.view', 'procurement.view', 'procurement.create', 'procurement.receive', 'purchases.view', 'purchases.create', 'categories.manage', 'branches.view'])->pluck('id'));
        $grant('Accounting', ['suppliers.view', 'procurement.view', 'purchases.view', 'exports.procurement', 'reports.daily-digest.view']);
    }
}
