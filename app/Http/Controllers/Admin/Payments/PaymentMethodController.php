<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Requests\Payments\StorePaymentMethodRequest;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Services\Organisation\AuditLogger;
use App\Services\Payments\DefaultPosPaymentMethod;
use App\Support\Security\EncryptedValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
                ->orderByDesc('is_default_for_pos')
                ->orderByDesc('is_system_default')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        StorePaymentMethodRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $method = PaymentMethod::query()->create([
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
            'created_by_account_id' => $actor->getKey(),
        ]);

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
            ],
        );

        return back()->with('status', 'Payment method created.');
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
