<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class RolePermissionPolicy
{
    /** @var array<string, list<string>> */
    private const MATRICES = [
        'sales' => [
            'dashboard.staff.view', 'sales.view.own', 'sales.create',
            'sales.payments.record', 'sales.returns.create',
            'vouchers.apply', 'quotes.convert',
            'customers.view.assigned', 'customers.create', 'customers.update.assigned',
            'documents.sales.print', 'catalog.sale-search',
        ],
        'inventory' => [
            'dashboard.inventory.view', 'products.view', 'products.create', 'products.update',
            'products.deactivate', 'categories.manage', 'brands.manage', 'tax-rates.manage',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust', 'reports.low-stock',
            'products.prices.adjust', 'products.zero-stock.manage',
            'documents.products.labels', 'catalog.inventory-search',
        ],
        'procurement' => [
            'dashboard.procurement.view', 'products.view', 'suppliers.view',
            'suppliers.create', 'suppliers.update', 'procurement.view',
            'procurement.create', 'procurement.receive', 'purchases.record',
            'supplier-bills.view', 'supplier-bills.create',
            'supplier-documents.download', 'catalog.procurement-search',
        ],
        'accounting' => [
            'dashboard.accounting.view', 'customers.receivables.view',
            'supplier-bills.view', 'supplier-bills.pay', 'reports.supplier-balances',
            'receipts.view', 'receipts.create', 'purchase_returns.view',
            'purchase_returns.create', 'assets.view', 'assets.manage',
            'operation_documents.download', 'documents.branding.manage',
            'accounting.accounts.view', 'accounting.accounts.manage',
            'accounting.journals.view', 'accounting.journals.create',
            'accounting.journals.reverse', 'accounting.periods.manage',
            'accounting.reports.view', 'accounting.sync', 'accounting.depreciation.post',
            'reports.hub.view', 'reports.export',
        ],
        'auditor' => [
            'dashboard.audit.view', 'company.view', 'branches.view', 'staff.view',
            'roles.view', 'products.view', 'suppliers.view', 'inventory.view',
            'inventory.movements.view', 'procurement.view', 'sales.view.all',
            'supplier-bills.view', 'supplier-returns.view', 'reports.hub.view',
            'accounting.accounts.view', 'accounting.journals.view',
            'accounting.reports.view', 'activity.view', 'activity.products.view',
            'security-events.view', 'audit-log.view', 'audit-log.export',
        ],
        'branch-manager' => [
            'dashboard.view', 'branches.view', 'staff.view', 'products.view',
            'inventory.view', 'inventory.movements.view', 'inventory.intake',
            'inventory.transfer', 'inventory.adjust', 'reports.low-stock',
            'sales.view.all', 'sales.create', 'sales.payments.record',
            'sales.returns.create', 'customers.view', 'customers.create',
            'customers.update', 'procurement.view', 'procurement.create',
            'procurement.approve', 'procurement.receive', 'reports.staff-performance',
            'documents.sales.print', 'catalog.sale-search', 'catalog.inventory-search',
        ],
    ];

    /** @param list<string> $requested @return list<string> */
    public static function constrain(string $name, string $slug, array $requested): array
    {
        $family = self::family($name, $slug);
        $requested = array_values(array_unique(array_filter($requested, 'is_string')));

        if ($family === null || in_array($family, ['system-owner', 'administrator'], true)) {
            return $requested;
        }

        return array_values(array_intersect($requested, self::MATRICES[$family] ?? []));
    }

    public static function family(string $name, string $slug): ?string
    {
        $identity = mb_strtolower(trim($name.' '.$slug));
        $patterns = [
            'system-owner' => '/\b(system[ _-]?owner|owner)\b/u',
            'administrator' => '/\b(admin|administrator)\b/u',
            'branch-manager' => '/\b(branch[ _-]?manager|store[ _-]?manager)\b/u',
            'sales' => '/\b(sales|cashier|salesperson|sales[ _-]?rep)\b/u',
            'inventory' => '/\b(inventory|stock|warehouse|storekeeper)\b/u',
            'procurement' => '/\b(procurement|purchasing|buyer)\b/u',
            'accounting' => '/\b(accounting|accountant|finance|bookkeeper)\b/u',
            'auditor' => '/\b(auditor|audit|compliance)\b/u',
        ];
        foreach ($patterns as $family => $pattern) {
            if (preg_match($pattern, $identity) === 1) return $family;
        }
        return null;
    }
}
