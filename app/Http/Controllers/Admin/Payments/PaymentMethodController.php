<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Requests\Payments\StorePaymentMethodRequest;
use App\Models\Account;
use App\Models\LedgerAccount;
use App\Models\PaymentMethod;
use App\Services\Organisation\AuditLogger;
use App\Services\Payments\DefaultPosPaymentMethod;
use App\Support\Security\EncryptedValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class PaymentMethodController
{
    public function __construct(
        private EncryptedValue $encrypted,
        private DefaultPosPaymentMethod $defaultPos,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::query()
                ->with('ledgerAccount:id,code,name')
                ->orderByDesc('is_default_for_pos')
                ->orderByDesc('is_system_default')
                ->orderBy('name')
                ->paginate(config('pagination.default', 10))
                ->withQueryString(),
            'bankAccounts' => LedgerAccount::query()
                ->where('type', 'asset')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(
        StorePaymentMethodRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $method = DB::transaction(function () use ($request, $actor): PaymentMethod {
            $ledgerAccountId = $this->resolveLedgerAccountId($request);

            return PaymentMethod::query()->create([
                'name' => $request->string('name')->trim()->toString(),
                'account_number_encrypted' => $request->filled('account_number')
                    ? $this->encrypted->encrypt(
                        $request->string('account_number')->trim()->toString(),
                    )
                    : null,
                'bank_name' => $request->filled('bank_name')
                    ? $request->string('bank_name')->trim()->toString()
                    : null,
                'description' => $request->filled('description')
                    ? $request->string('description')->trim()->toString()
                    : null,
                'is_system_default' => false,
                'is_default_for_pos' => false,
                'is_active' => true,
                'ledger_account_id' => $ledgerAccountId,
                'created_by_account_id' => $actor->getKey(),
            ]);
        });

        if ($request->boolean('is_default_for_pos')) {
            $this->defaultPos->set($method);
        }

        $this->audit->record(
            $request,
            'payment-method.created',
            'payment_method',
            $method,
            after: [
                'name' => $method->name,
                'bank_name' => $method->bank_name,
                'is_default_for_pos' => $method->is_default_for_pos,
                'is_active' => $method->is_active,
                'ledger_account_id' => $method->ledger_account_id,
            ],
        );

        return back()->with('status', 'Payment method created.');
    }

    /**
     * Resolve which ledger account this payment method should post against.
     *
     * Priority: an explicitly selected existing account, then a newly
     * created one (named after the payment method, under the bank/asset
     * range), then null so postings fall back to the legacy name-matching
     * heuristic in OperationalAccountingProjector until an admin links one.
     */
    private function resolveLedgerAccountId(
        StorePaymentMethodRequest $request,
    ): ?string {
        if ($request->filled('ledger_account_id')) {
            return $request->string('ledger_account_id')->toString();
        }

        if (! $request->filled('new_ledger_account_name')) {
            return null;
        }

        $highestCode = (int) LedgerAccount::query()
            ->where('code', '>=', '1010')
            ->where('code', '<', '1090')
            ->orderByDesc('code')
            ->value('code') ?: 1010;

        $nextCode = (string) min($highestCode + 1, 1089);

        $account = LedgerAccount::query()->create([
            'code' => $nextCode,
            'name' => $request->string('new_ledger_account_name')
                ->trim()->toString(),
            'type' => 'asset',
            'parent_id' => null,
            'is_control_account' => false,
            'is_system' => false,
            'is_active' => true,
            'allow_manual_posting' => true,
            'description' => 'Auto-created for payment method: '
                .$request->string('name')->trim()->toString(),
        ]);

        return (string) $account->getKey();
    }

    public function setDefault(
        Request $request,
        PaymentMethod $method,
    ): RedirectResponse {
        $this->defaultPos->set($method);

        $this->audit->record(
            $request,
            'payment-method.default-pos',
            'payment_method',
            $method,
            after: ['is_default_for_pos' => true],
        );

        return back()->with(
            'status',
            'Default POS payment method updated.',
        );
    }

    public function toggle(
        Request $request,
        PaymentMethod $method,
    ): RedirectResponse {
        if ($method->is_system_default && $method->is_active) {
            throw new \DomainException(
                'System payment methods cannot be disabled.',
            );
        }

        if ($method->is_default_for_pos && $method->is_active) {
            throw new \DomainException(
                'Choose another POS default before disabling this method.',
            );
        }

        $method->forceFill([
            'is_active' => ! $method->is_active,
        ])->save();

        $this->audit->record(
            $request,
            'payment-method.toggled',
            'payment_method',
            $method,
            after: ['is_active' => $method->is_active],
        );

        return back()->with('status', 'Payment method updated.');
    }
}
