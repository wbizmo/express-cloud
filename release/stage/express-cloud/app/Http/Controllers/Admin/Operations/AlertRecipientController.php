<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Requests\Admin\Operations\StoreAlertRecipientRequest;
use App\Models\Account;
use App\Models\AlertRecipient;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class AlertRecipientController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.operations.alert-recipients', [
            'recipients' => AlertRecipient::query()
                ->orderByDesc('is_active')
                ->orderBy('email')
                ->get(),
        ]);
    }

    public function store(
        StoreAlertRecipientRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $recipient = AlertRecipient::query()->create([
            'email' => mb_strtolower(
                $request->string('email')->trim()->toString(),
            ),
            'label' => $request->filled('label')
                ? $request->string('label')->trim()->toString()
                : null,
            'is_active' => true,
            'added_by_account_id' => $actor->getKey(),
        ]);

        $this->audit->record(
            $request,
            'alert-recipient.created',
            'alert_recipient',
            $recipient,
            after: [
                'email' => $recipient->email,
                'label' => $recipient->label,
            ],
        );

        return back()->with('status', 'Alert recipient added.');
    }

    public function toggle(
        Request $request,
        AlertRecipient $recipient,
    ): RedirectResponse {
        $recipient->forceFill([
            'is_active' => ! $recipient->is_active,
        ])->save();

        $this->audit->record(
            $request,
            'alert-recipient.toggled',
            'alert_recipient',
            $recipient,
            after: ['is_active' => $recipient->is_active],
        );

        return back()->with('status', 'Alert recipient updated.');
    }
}
