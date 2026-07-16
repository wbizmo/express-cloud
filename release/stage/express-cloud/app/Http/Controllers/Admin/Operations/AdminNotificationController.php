<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AdminNotificationController
{
    public function markRead(
        Request $request,
        AdminNotification $notification,
    ): RedirectResponse {
        $notification->forceFill([
            'read_at' => now(),
        ])->save();

        return back()->with('status', 'Notification marked as read.');
    }
}
