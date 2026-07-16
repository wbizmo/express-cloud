<?php

declare(strict_types=1);

namespace App\Actions\SupplierFinance;

use App\Enums\SupplierFinance\SupplierBillStatus;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\SupplierBill;
use App\Models\SupplierBillPayment;
use App\Services\Catalog\MoneyInput;
use Illuminate\Support\Facades\DB;

final readonly class RecordSupplierBillPayment
{
    public function __construct(private MoneyInput $money) {}

    public function execute(
        SupplierBill $bill,
        PaymentMethod $method,
        Account $actor,
        mixed $amount,
        ?string $reference,
    ): SupplierBillPayment {
        if (! $method->is_active) {
            throw new \DomainException(
                'Inactive payment methods cannot record supplier payments.',
            );
        }

        return DB::transaction(function () use (
            $bill,
            $method,
            $actor,
            $amount,
            $reference,
        ): SupplierBillPayment {
            /** @var SupplierBill $lockedBill */
            $lockedBill = SupplierBill::query()
                ->whereKey($bill->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $status = $lockedBill->status
                instanceof SupplierBillStatus
                ? $lockedBill->status
                : SupplierBillStatus::from(
                    (string) $lockedBill->status,
                );

            if (! $status->payable()) {
                throw new \DomainException(
                    'This supplier bill is not open for payment.',
                );
            }

            $amountKobo = $this->money->toKobo($amount) ?? 0;

            if ($amountKobo <= 0) {
                throw new \InvalidArgumentException(
                    'Supplier payment amount must be greater than zero.',
                );
            }

            if ($amountKobo > $lockedBill->balanceDueKobo()) {
                throw new \DomainException(
                    'Supplier payment exceeds the outstanding bill balance.',
                );
            }

            $payment = SupplierBillPayment::query()->create([
                'supplier_bill_id' => $lockedBill->getKey(),
                'payment_method_id' => $method->getKey(),
                'recorded_by_account_id' => $actor->getKey(),
                'amount_kobo' => $amountKobo,
                'reference' => $reference,
                'paid_at' => now(),
            ]);

            $newPaid = $lockedBill->paid_kobo + $amountKobo;

            $lockedBill->forceFill([
                'paid_kobo' => $newPaid,
                'status' => SupplierBillStatus::fromPayment(
                    $newPaid,
                    $lockedBill->total_kobo,
                ),
            ])->save();

            return $payment;
        });
    }
}
