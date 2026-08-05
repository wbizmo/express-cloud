<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff\Commercial;

use App\Actions\Commercial\ApplyVoucherToSale;
use App\Actions\Sales\AddSalePayment;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Services\Commercial\SaleAccess;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SaleSettlementController
{
    public function __construct(
        private SaleAccess $access,
        private AddSalePayment $payments,
        private ApplyVoucherToSale $vouchers,
        private AuditLogger $audit,
    ) {}

    public function payment(
        Request $request,
        Sale $sale,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        abort_unless($this->access->canView($actor, $sale), 403);

        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:100'],
            'payment_method_id' => [
                'required',
                'ulid',
                'exists:payment_methods,id',
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:160'],
        ]);

        /** @var PaymentMethod $method */
        $method = PaymentMethod::query()->findOrFail(
            $validated['payment_method_id'],
        );

        $payment = $this->payments->execute(
            $sale,
            $method,
            $actor,
            $validated['amount'],
            $validated['reference'] ?? null,
            (string) $validated['idempotency_key'],
        );

        $this->audit->record(
            $request,
            'sale.payment-recorded',
            'payment',
            $payment,
            after: [
                'sale_id' => $sale->getKey(),
                'amount_kobo' => $payment->amount_kobo,
            ],
        );

        return back()->with('status', 'Payment recorded.');
    }

    public function voucher(
        Request $request,
        Sale $sale,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();
        abort_unless($this->access->canView($actor, $sale), 403);

        $validated = $request->validate([
            'voucher_code' => ['required', 'string', 'max:80'],
        ]);

        $updated = $this->vouchers->execute(
            $sale,
            $validated['voucher_code'],
            $actor,
        );

        $this->audit->record(
            $request,
            'sale.voucher-applied',
            'sale',
            $updated,
            after: [
                'discount_voucher_id' => $updated->discount_voucher_id,
                'discount_amount_kobo' => $updated->discount_amount_kobo,
            ],
        );

        return back()->with('status', 'Voucher applied.');
    }
}
