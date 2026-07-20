<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WorkforceAnnouncementController
{
    public function dismiss(
        Request $request,
        AdminNotification $announcement,
    ): RedirectResponse {
        abort_unless(
            $announcement->entity_type === 'workforce_announcement',
            404,
        );

        $announcement->forceFill(['resolved_at' => now()])->save();

        return back();
    }
}
