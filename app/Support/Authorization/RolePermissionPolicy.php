<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class RolePermissionPolicy
{
    /** @var list<string> */
    private const SYSTEM_OWNER_ALIASES = [
        'super-admin',
        'admin',
        'company-owner',
    ];

    /** @var array<string, list<string>> */
    private const PERMISSIONS = [
        'system-owner' => [
            'dashboard.view', 'dashboard.staff.view',
            'branches.view', 'branches.create', 'branches.deactivate',
            'staff.view', 'staff.create', 'staff.access-key.reveal', 'staff.suspend',
            'staff.sessions.view', 'staff.sessions.revoke',
            'roles.view', 'roles.create',
            'products.view', 'products.create', 'products.update',
            'products.import', 'products.import-history',
            'products.prices.adjust',
            'categories.manage', 'brands.manage', 'tax-rates.manage',
            'suppliers.view', 'suppliers.create',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust',
            'procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive',
            'sales.view', 'sales.view.all', 'sales.create', 'sales.payments.record',
            'sales.returns.create', 'sales.edit', 'sales.void', 'sales.export',
            'customers.view', 'customers.create', 'customers.update',
            'customers.receivables.view',
            'payment-methods.view', 'payment-methods.manage',
            'vouchers.manage', 'vouchers.apply',
            'reports.hub.view', 'reports.export', 'reports.low-stock', 'reports.staff-performance',
            'reports.supplier-balances',
            'documents.sales.print', 'documents.products.labels', 'documents.branding.manage',
            'operation_documents.download',
            'catalog.sale-search', 'catalog.inventory-search',
            'accounting.accounts.view', 'accounting.accounts.manage',
            'accounting.journals.view', 'accounting.journals.create', 'accounting.journals.reverse',
            'accounting.periods.manage',
            'accounting.reports.view', 'accounting.reports.export',
            'accounting.sync', 'accounting.depreciation.post',
            'insights.view', 'insights.generate', 'insights.dismiss',
            'lisa.chat', 'lisa.audit.view',
            'activity.view', 'activity.products.view', 'activity.export',
            'security.sessions.view', 'security.sessions.terminate',
            'audit-log.view', 'audit-log.export',
            'security-events.view',
            'backups.view', 'backups.create', 'backups.verify',
            'alerts.manage-recipients', 'alerts.view',
            'settings.business.manage',
            'exports.sales', 'exports.inventory', 'exports.procurement',
            'supplier-bills.view', 'supplier-bills.create', 'supplier-bills.pay',
            'supplier-documents.download',
            'supplier-returns.view', 'supplier-returns.create',
            'receipts.view', 'receipts.create',
            'purchase_returns.view', 'purchase_returns.create',
            'assets.view', 'assets.manage',
            'purchases.record',
            'operation_documents.download',
            'accounting-operations.*',
            'accounting.journals.manage',
        ],
        'super-admin' => [],
        'admin' => [],
        'company-owner' => [],
        'branch-manager' => [
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
        ],
        'auditor' => [
            'dashboard.view', 'products.view', 'inventory.movements.view',
            'procurement.view', 'sales.view.all', 'supplier-bills.view',
            'supplier-returns.view', 'reports.hub.view',
            'accounting.accounts.view', 'accounting.journals.view',
            'accounting.reports.view', 'accounting.reports.export',
            'activity.view', 'activity.products.view', 'security-events.view',
            'audit-log.view', 'audit-log.export', 'sales.export',
        ],
        'cashier' => [
            'dashboard.view', 'products.view', 'sales.create',
            'sales.view', 'sales.payments.record', 'sales.returns.create',
            'customers.view', 'customers.create', 'documents.sales.print',
            'catalog.sale-search', 'catalog.inventory-search',
            'payment-methods.view', 'vouchers.apply',
        ],
    ];

    /** @return array<string, list<string>> */
    public static function all(): array
    {
        $permissions = self::PERMISSIONS;
        $systemOwnerPermissions = self::PERMISSIONS['system-owner'];

        foreach (self::SYSTEM_OWNER_ALIASES as $alias) {
            $permissions[$alias] = $systemOwnerPermissions;
        }

        return $permissions;
    }

    /** @return list<string> */
    public static function forRole(string $slug): array
    {
        if (in_array($slug, self::SYSTEM_OWNER_ALIASES, true)) {
            return self::PERMISSIONS['system-owner'];
        }

        return self::PERMISSIONS[$slug] ?? [];
    }
}
