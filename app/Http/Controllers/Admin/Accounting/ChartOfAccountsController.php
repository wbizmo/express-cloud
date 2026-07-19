<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Accounting;

use App\Enums\Accounting\AccountType;
use App\Http\Requests\Admin\Accounting\StoreLedgerAccountRequest;
use App\Http\Requests\Admin\Accounting\UpdateLedgerAccountRequest;
use App\Models\LedgerAccount;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final readonly class ChartOfAccountsController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.accounting.chart-of-accounts.index', [
            'accounts' => LedgerAccount::query()
                ->with('parent')
                ->orderBy('code')
                ->paginate(50),
            'parentOptions' => LedgerAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'types' => AccountType::cases(),
        ]);
    }

    public function store(
        StoreLedgerAccountRequest $request,
    ): RedirectResponse {
        $account = LedgerAccount::query()->create([
            'code' => Str::upper(
                $request->string('code')->trim()->toString(),
            ),
            'name' => $request->string('name')->trim()->toString(),
            'type' => $request->string('type')->toString(),
            'parent_id' => $request->filled('parent_id')
                ? $request->string('parent_id')->toString()
                : null,
            'is_control_account' => $request->boolean('is_control_account'),
            'is_system' => false,
            'is_active' => true,
            'allow_manual_posting' => $request->boolean(
                'allow_manual_posting',
                true,
            ),
            'description' => $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
        ]);

        $this->audit->record(
            $request,
            'ledger_account.created',
            'ledger_account',
            $account,
            after: [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type instanceof AccountType
                    ? $account->type->value
                    : (string) $account->type,
            ],
        );

        return back()->with('status', 'Ledger account created.');
    }

    public function edit(LedgerAccount $ledgerAccount): View
    {
        return view('admin.accounting.chart-of-accounts.edit', [
            'ledgerAccount' => $ledgerAccount,
        ]);
    }

    public function update(
        UpdateLedgerAccountRequest $request,
        LedgerAccount $ledgerAccount,
    ): RedirectResponse {
        if ($ledgerAccount->is_system) {
            return redirect()
                ->route('admin.accounting.chart-of-accounts.index')
                ->with('status', 'System accounts cannot be edited.');
        }

        $before = [
            'name' => $ledgerAccount->name,
            'is_active' => $ledgerAccount->is_active,
            'allow_manual_posting' => $ledgerAccount->allow_manual_posting,
            'description' => $ledgerAccount->description,
        ];

        $ledgerAccount->update([
            'name' => $request->string('name')->trim()->toString(),
            'is_active' => $request->boolean('is_active', true),
            'allow_manual_posting' => $request->boolean(
                'allow_manual_posting',
                true,
            ),
            'description' => $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
        ]);

        $this->audit->record(
            $request,
            'ledger_account.updated',
            'ledger_account',
            $ledgerAccount,
            before: $before,
            after: [
                'name' => $ledgerAccount->name,
                'is_active' => $ledgerAccount->is_active,
                'allow_manual_posting' => $ledgerAccount->allow_manual_posting,
                'description' => $ledgerAccount->description,
            ],
        );

        return redirect()
            ->route('admin.accounting.chart-of-accounts.index')
            ->with('status', 'Ledger account updated.');
    }
}
