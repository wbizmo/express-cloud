<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class PermissionCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'organisation' => [
                'company.view' => 'View company details',
                'company.update' => 'Update company details',
                'branches.view' => 'View branches',
                'branches.create' => 'Create branches',
                'branches.update' => 'Update branches',
                'branches.deactivate' => 'Deactivate branches',
            ],
            'staff' => [
                'staff.view' => 'View staff accounts',
                'staff.create' => 'Create staff accounts',
                'staff.update' => 'Update staff accounts',
                'staff.suspend' => 'Suspend staff accounts',
                'staff.reactivate' => 'Reactivate staff accounts',
                'staff.revoke' => 'Revoke staff accounts',
                'staff.access-key.reveal' => 'Reveal staff access keys',
                'staff.access-key.regenerate' => 'Regenerate staff access keys',
                'staff.sessions.view' => 'View active staff sessions',
                'staff.sessions.revoke' => 'Revoke staff sessions',
            ],
            'authorization' => [
                'roles.view' => 'View roles',
                'roles.create' => 'Create roles',
                'roles.update' => 'Update roles',
                'roles.delete' => 'Delete custom roles',
                'permissions.assign' => 'Assign permissions',
            ],
            'catalog' => [
                'products.view' => 'View products',
                'products.create' => 'Create products',
                'products.update' => 'Update products',
                'products.deactivate' => 'Deactivate products',
                'categories.manage' => 'Manage product categories',
                'brands.manage' => 'Manage brands',
                'tax-rates.manage' => 'Manage tax rates',
                'suppliers.view' => 'View suppliers',
                'suppliers.create' => 'Create suppliers',
                'suppliers.update' => 'Update suppliers',
                'suppliers.archive' => 'Archive suppliers',
                'products.import' => 'Import products from Excel',
                'products.import-history' => 'View product import history',
                'inventory.view' => 'View branch inventory',
                'inventory.movements.view' => 'View stock movement ledger',
                'inventory.intake' => 'Record stock intake',
                'inventory.transfer' => 'Transfer stock between branches',
                'inventory.adjust' => 'Adjust stock with a reason',
                'procurement.view' => 'View purchase orders',
                'procurement.create' => 'Create purchase orders',
                'procurement.approve' => 'Approve purchase orders',
                'procurement.receive' => 'Receive goods against purchase orders',
                'reports.low-stock' => 'View low-stock report',
                'customers.view' => 'View customers',
                'customers.create' => 'Create customers',
                'customers.update' => 'Update customers',
                'payment-methods.view' => 'View payment methods',
                'payment-methods.manage' => 'Manage payment methods',
            ],
            'security' => [
                'security-events.view' => 'View security events',
                'audit-log.view' => 'View audit logs',
                'audit-log.export' => 'Export audit logs',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (self::grouped() as $group) {
            foreach (array_keys($group) as $permission) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }
}
