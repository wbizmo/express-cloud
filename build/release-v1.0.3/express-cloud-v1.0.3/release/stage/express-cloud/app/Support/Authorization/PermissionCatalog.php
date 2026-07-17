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
                'sales.view' => 'View sales',
                'sales.create' => 'Create invoices, quotes, and POS sales',
                'sales.payments' => 'Record sale payments',
                'sales.convert-quotes' => 'Convert quotes',
                'supplier-bills.view' => 'View supplier bills',
                'supplier-bills.create' => 'Create supplier bills',
                'supplier-bills.pay' => 'Record supplier bill payments',
                'supplier-documents.download' => 'Download supplier documents',
                'supplier-returns.view' => 'View supplier returns',
                'supplier-returns.create' => 'Create supplier returns',
                'reports.supplier-balances' => 'View supplier balances',
                'dashboard.view' => 'View admin dashboard',
                'alerts.view' => 'View operational alerts',
                'alerts.manage-recipients' => 'Manage alert recipients',
                'settings.business.manage' => 'Manage business settings',
                'reports.staff-performance' => 'View staff performance',
                'reports.hub.view' => 'View reports hub',
                'reports.export' => 'Export reports',
                'documents.sales.print' => 'Print and download sale documents',
                'documents.products.labels' => 'Print product labels',
                'activity.view' => 'View system activity log',
                'activity.products.view' => 'View product activity',
                'security.sessions.view' => 'View live sessions',
                'security.sessions.terminate' => 'Terminate live sessions',
            ],
            'security' => [
                'security-events.view' => 'View security events',
                'audit-log.view' => 'View audit logs',
                'audit-log.export' => 'Export audit logs',
            ],

            'Commercial' => [
                'sales.view.own' => 'View own sales',
                'sales.view.all' => 'View all sales',
                'sales.payments.record' => 'Record sale payments',
                'sales.returns.create' => 'Create sale returns',
                'vouchers.manage' => 'Manage discount vouchers',
                'vouchers.apply' => 'Apply discount vouchers',
                'customers.receivables.view' => 'View customer receivables',
                'purchases.record' => 'Record direct purchases',

                'api.tokens.manage' => 'Manage API tokens',

                'quotes.convert' => 'Convert quotes',
            ],

            'Backup and Recovery' => [
                'backups.view' => 'View backups',
                'backups.create' => 'Create backups',
                'backups.verify' => 'Verify backups',
            ],
            'Accounting Operations' => [
                'documents.branding.manage' => 'Manage document branding',
                'receipts.view' => 'View standalone receipts',
                'receipts.create' => 'Create standalone receipts',
                'purchase_returns.view' => 'View purchase returns',
                'purchase_returns.create' => 'Create purchase returns',
                'assets.view' => 'View fixed assets',
                'assets.manage' => 'Manage fixed assets',
                'operation_documents.download' => 'Download operation documents',

                'accounting.accounts.view' => 'View chart of accounts',
                'accounting.accounts.manage' => 'Manage chart of accounts',
                'accounting.journals.view' => 'View journals',
                'accounting.journals.create' => 'Create manual journals',
                'accounting.journals.reverse' => 'Reverse journals',
                'accounting.periods.manage' => 'Manage accounting periods',
                'accounting.reports.view' => 'View financial reports',
                'accounting.sync' => 'Synchronize operational accounting',
                'accounting.depreciation.post' => 'Post fixed-asset depreciation',
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
