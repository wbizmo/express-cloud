<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Enums\Operations\AdminNotificationType;
use App\Models\AdminNotification;
use App\Models\LowStockAlert;

final class AdminNotificationService
{
    public function refreshLowStock(LowStockAlert $alert): void
    {
        if ($alert->resolved_at !== null) {
            AdminNotification::query()
                ->where(
                    'notification_type',
                    AdminNotificationType::LowStock->value,
                )
                ->where('entity_type', 'low_stock_alert')
                ->where('entity_id', $alert->getKey())
                ->whereNull('resolved_at')
                ->update(['resolved_at' => now()]);

            return;
        }

        $notification = AdminNotification::query()
            ->where(
                'notification_type',
                AdminNotificationType::LowStock->value,
            )
            ->where('entity_type', 'low_stock_alert')
            ->where('entity_id', $alert->getKey())
            ->whereNull('resolved_at')
            ->first();

        $title = 'Low stock requires attention';
        $message = sprintf(
            'A tracked product is at or below its branch minimum.',
        );

        if ($notification instanceof AdminNotification) {
            $notification->forceFill([
                'title' => $title,
                'message' => $message,
                'branch_id' => $alert->branch_id,
                'occurred_at' => now(),
            ])->save();

            return;
        }

        AdminNotification::query()->create([
            'notification_type' => AdminNotificationType::LowStock,
            'title' => $title,
            'message' => $message,
            'entity_type' => 'low_stock_alert',
            'entity_id' => $alert->getKey(),
            'branch_id' => $alert->branch_id,
            'occurred_at' => now(),
        ]);
    }
}
