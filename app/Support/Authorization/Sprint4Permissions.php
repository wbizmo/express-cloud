<?php

declare(strict_types=1);

namespace App\Support\Authorization;

final class Sprint4Permissions
{
    public static function grouped(): array
    {
        return [
            'crm' => ['customers.view', 'customers.create', 'customers.deprecate', 'customers.restore'],
            'inventory' => ['inventory.view', 'inventory.transfer', 'inventory.intake', 'inventory.adjust', 'products.view', 'products.create', 'products.import', 'categories.manage', 'brands.manage'],
            'procurement' => ['suppliers.view', 'suppliers.create', 'procurement.view', 'procurement.create', 'procurement.receive', 'procurement.approve', 'purchases.view', 'purchases.create'],
            'audit' => ['activity.view', 'activity.export', 'activity.view.all-branches'],
            'lisa' => ['insights.view', 'lisa.chat', 'lisa.conversations.search', 'lisa.audit.view', 'lisa.generate'],
            'exports' => ['exports.sales', 'exports.inventory', 'exports.procurement', 'exports.reports'],
            'records' => ['records.deprecate', 'records.restore', 'records.archive.view'],
            'reports' => ['reports.daily-digest.view', 'reports.daily-digest.generate', 'reports.daily-digest.email'],
        ];
    }

    public static function all(): array
    {
        $all = [];
        foreach (self::grouped() as $p) {
            array_push($all, ...$p);
        }

return array_values(array_unique($all));
    }
}
