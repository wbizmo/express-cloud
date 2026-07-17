<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Requests\Admin\Operations\UpdateBusinessSettingsRequest;
use App\Models\BusinessSetting;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class BusinessSettingsController
{
    public function __construct(private AuditLogger $audit) {}

    public function edit(): View
    {
        return view('admin.operations.settings', [
            'settings' => BusinessSetting::current(),
        ]);
    }

    public function update(
        UpdateBusinessSettingsRequest $request,
    ): RedirectResponse {
        $settings = BusinessSetting::current();
        $logoPath = $settings->business_logo_path;

        if ($request->hasFile('business_logo')) {
            $logoPath = $request->file(
                'business_logo',
            )->store('business/branding', 'public');
        }

        $settings->forceFill([
            'business_name' => $request->string(
                'business_name',
            )->trim()->toString(),
            'business_logo_path' => $logoPath,
            'head_office_address' => $request->string(
                'head_office_address',
            )->trim()->toString(),
            'end_of_day_digest_time' => $request->string(
                'end_of_day_digest_time',
            )->toString().':00',
            'session_inactivity_minutes' => $request->integer(
                'session_inactivity_minutes',
            ),
        ])->save();

        $this->audit->record(
            $request,
            'business-settings.updated',
            'business_setting',
            'default',
            after: [
                'business_name' => $settings->business_name,
                'end_of_day_digest_time' => $settings->end_of_day_digest_time,
                'session_inactivity_minutes' => $settings->session_inactivity_minutes,
            ],
        );

        return back()->with('status', 'Business settings updated.');
    }
}
