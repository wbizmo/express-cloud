<?php

declare(strict_types=1);

return [
    'primary' => [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => null],
                ['label' => 'Create Sale', 'icon' => 'shopping-cart', 'route' => null],
                ['label' => 'Sales', 'icon' => 'receipt-text', 'route' => null],
                ['label' => 'Quotes', 'icon' => 'file-text', 'route' => null],
                ['label' => 'Customers', 'icon' => 'users', 'route' => null],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                ['label' => 'Products', 'icon' => 'package', 'route' => null],
                ['label' => 'Inventory', 'icon' => 'warehouse', 'route' => null],
                ['label' => 'Purchasing', 'icon' => 'truck', 'route' => null],
                ['label' => 'Reports', 'icon' => 'chart-no-axes-combined', 'route' => null],
                ['label' => 'Lisa AI', 'icon' => 'bot-message-square', 'route' => null],
            ],
        ],
    ],

    'secondary' => [
        ['label' => 'Settings', 'icon' => 'settings', 'route' => null],
        ['label' => 'Help', 'icon' => 'circle-help', 'route' => null],
    ],
];
