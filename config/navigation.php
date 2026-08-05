<?php

declare(strict_types=1);

return [
    'primary' => [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Admin Dashboard', 'icon' => 'layout-dashboard', 'route' => 'admin.dashboard', 'permission' => 'dashboard.view'],
                ['label' => 'Create Sale', 'icon' => 'shopping-cart', 'route' => 'admin.sales.create', 'permission' => 'sales.create'],
                ['label' => 'Sales & Quotes', 'icon' => 'receipt-text', 'route' => 'admin.sales.index', 'permission_any' => ['sales.view', 'sales.view.own', 'sales.view.all']],
                ['label' => 'Customers & Credit', 'icon' => 'users', 'route' => 'admin.customers.index', 'permission' => 'customers.view'],
                ['label' => 'Receivables', 'icon' => 'hand-coins', 'route' => 'admin.commercial.receivables.index', 'permission' => 'customers.receivables.view'],
            ],
        ],
        [
            'label' => 'Catalogue & Stock',
            'items' => [
                ['label' => 'Products', 'icon' => 'package', 'route' => 'admin.catalog.products.index', 'permission' => 'products.view'],
                ['label' => 'Bulk Price Update', 'icon' => 'badge-dollar-sign', 'route' => 'admin.catalog.price-adjustments.index', 'permission' => 'products.prices.adjust'],
                ['label' => 'Categories', 'icon' => 'folders', 'route' => 'admin.catalog.categories.index', 'permission' => 'categories.manage'],
                ['label' => 'Brands', 'icon' => 'tags', 'route' => 'admin.catalog.brands.index', 'permission' => 'brands.manage'],
                ['label' => 'Product Import', 'icon' => 'file-up', 'route' => 'admin.imports.products.index', 'permission_any' => ['products.import', 'products.import-history']],
                ['label' => 'Inventory', 'icon' => 'warehouse', 'route' => 'admin.inventory.index', 'permission' => 'inventory.view'],
                ['label' => 'Stock Movements', 'icon' => 'arrow-left-right', 'route' => 'admin.inventory.movements', 'permission' => 'inventory.movements.view'],
                ['label' => 'Low Stock', 'icon' => 'triangle-alert', 'route' => 'admin.reports.low-stock', 'permission' => 'reports.low-stock'],
            ],
        ],
        [
            'label' => 'Purchasing',
            'items' => [
                ['label' => 'Suppliers', 'icon' => 'contact-round', 'route' => 'admin.catalog.suppliers.index', 'permission' => 'suppliers.view'],
                ['label' => 'Purchase Orders', 'icon' => 'truck', 'route' => 'admin.procurement.orders.index', 'permission' => 'procurement.view'],
                ['label' => 'Direct Purchases', 'icon' => 'package-plus', 'route' => 'admin.commercial.purchases.index', 'permission' => 'purchases.view'],
                ['label' => 'Supplier Bills', 'icon' => 'file-text', 'route' => 'admin.supplier-finance.bills.index', 'permission' => 'supplier-bills.view'],
                ['label' => 'Supplier Returns', 'icon' => 'undo-2', 'route' => 'admin.supplier-finance.returns.index', 'permission' => 'supplier-returns.view'],
            ],
        ],
        [
            'label' => 'Finance & Intelligence',
            'items' => [
                ['label' => 'Chart of Accounts', 'icon' => 'list-tree', 'route' => 'admin.accounting.chart-of-accounts.index', 'permission' => 'accounting.accounts.view'],
                ['label' => 'Journal Entries', 'icon' => 'pen-line', 'route' => 'admin.accounting.journal-entries.index', 'permission' => 'accounting.journals.view'],
                ['label' => 'Opening Balance', 'icon' => 'scale', 'route' => 'admin.accounting.opening-balance.create', 'permission' => 'accounting.journals.manage'],
                ['label' => 'Batch Journal Entry', 'icon' => 'layers', 'route' => 'admin.accounting.batch-journal.create', 'permission' => 'accounting.journals.manage'],
                ['label' => 'Accounting', 'icon' => 'landmark', 'route' => 'admin.accounting.reports.index', 'permission' => 'accounting.reports.view'],
                ['label' => 'Fixed Assets', 'icon' => 'building-2', 'route' => 'admin.accounting-operations.assets.index', 'permission' => 'assets.view'],
                ['label' => 'Reports', 'icon' => 'chart-no-axes-combined', 'route' => 'admin.reports.hub', 'permission' => 'reports.hub.view'],
                ['label' => 'HR & Performance', 'icon' => 'users-round', 'route' => 'admin.reports.staff-performance', 'permission' => 'reports.staff-performance'],
                ['label' => 'Lisa AI', 'icon' => 'bot-message-square', 'route' => 'admin.insights.index', 'permission' => 'insights.view'],
                ['label' => 'Chat with Lisa', 'icon' => 'messages-square', 'route' => 'admin.insights.chat.index', 'permission' => 'lisa.chat'],
            ],
        ],
        [
            'label' => 'Administration',
            'items' => [
                ['label' => 'Branches', 'icon' => 'map-pin-house', 'route' => 'admin.branches.index', 'permission' => 'branches.view'],
                ['label' => 'Staff', 'icon' => 'user-cog', 'route' => 'admin.staff.index', 'permission' => 'staff.view'],
                ['label' => 'Roles & Permissions', 'icon' => 'shield-check', 'route' => 'admin.roles.index', 'permission' => 'roles.view'],
                ['label' => 'Payment Methods', 'icon' => 'credit-card', 'route' => 'admin.payment-methods.index', 'permission' => 'payment-methods.view'],
                ['label' => 'Business Settings', 'icon' => 'settings', 'route' => 'admin.operations.settings.edit', 'permission' => 'settings.business.manage'],
                ['label' => 'Activity Log', 'icon' => 'history', 'route' => 'admin.activity.index', 'permission' => 'activity.view'],
                ['label' => 'Lisa Chat Audit', 'icon' => 'bot', 'route' => 'admin.insights.chat.audit', 'permission' => 'lisa.audit.view'],
                ['label' => 'Live Sessions', 'icon' => 'monitor-smartphone', 'route' => 'admin.security.sessions.index', 'permission' => 'security.sessions.view'],
                ['label' => 'Backups', 'icon' => 'database-backup', 'route' => 'admin.operations.backups.index', 'permission' => 'backups.view'],
            ],
        ],
    ],
    'secondary' => [
        ['label' => 'Profile', 'icon' => 'circle-user-round', 'route' => 'staff.profile.show', 'permission' => null],
    ],
];
