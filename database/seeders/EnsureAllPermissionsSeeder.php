<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Authorization\PermissionCatalog;
use Illuminate\Database\Seeder;

final class EnsureAllPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure all permissions from PermissionCatalog exist
        foreach (PermissionCatalog::grouped() as $group => $items) {
            foreach ($items as $slug => $name) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'group' => $group,
                        'description' => $name,
                    ]
                );
            }
        }

        $allPerms = Permission::query()->pluck('id');
        foreach (['system-owner', 'super-admin', 'admin', 'company-owner'] as $slug) {
            $role = Role::query()->where('slug', $slug)->first();
            if ($role) {
                $role->permissions()->sync($allPerms);
            }
        }

        // Branch manager subset
        $bmSlugs = [
            'dashboard.view', 'branches.view', 'staff.view', 'products.view',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust', 'reports.low-stock',
            'sales.view.all', 'sales.create', 'sales.payments.record',
            'sales.returns.create', 'sales.export', 'customers.view', 'customers.create',
            'customers.update', 'procurement.view', 'procurement.create',
            'procurement.approve', 'procurement.receive', 'reports.staff-performance',
            'documents.sales.print', 'catalog.sale-search', 'catalog.inventory-search',
            'payment-methods.view', 'vouchers.apply', 'suppliers.view',
            'reports.supplier-balances', 'supplier-bills.view', 'supplier-returns.view',
            'accounting.accounts.view', 'accounting.journals.view',
            'accounting.reports.view', 'accounting.reports.export',
            'activity.view', 'activity.products.view', 'security-events.view',
            'audit-log.view', 'audit-log.export',
        ];
        $bmPerms = Permission::query()->whereIn('slug', $bmSlugs)->pluck('id');
        $role = Role::query()->where('slug', 'branch-manager')->first();
        if ($role) {
            $role->permissions()->sync($bmPerms);
        }

        // Auditor subset
        $audSlugs = [
            'dashboard.view', 'products.view', 'inventory.movements.view',
            'procurement.view', 'sales.view.all', 'supplier-bills.view',
            'supplier-returns.view', 'reports.hub.view',
            'accounting.accounts.view', 'accounting.journals.view',
            'accounting.reports.view', 'accounting.reports.export',
            'activity.view', 'activity.products.view', 'security-events.view',
            'audit-log.view', 'audit-log.export', 'sales.export',
        ];
        $audPerms = Permission::query()->whereIn('slug', $audSlugs)->pluck('id');
        $role = Role::query()->where('slug', 'auditor')->first();
        if ($role) {
            $role->permissions()->sync($audPerms);
        }
    }
}
