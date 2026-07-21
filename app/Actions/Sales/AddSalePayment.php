<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Enums\Sales\SaleStatus;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Services\Catalog\MoneyInput;
use Illuminate\Support\Facades\DB;

final readonly class AddSalePayment
{
    public function __construct(private MoneyInput $money) {}

    public function execute(
        Sale $sale,
        PaymentMethod $method,
        Account $actor,
        mixed $amount,
        ?string $reference,
    ): Payment {
        if (! $method->is_active) {
            throw new \DomainException(
                'Inactive payment methods cannot receive payments.',
            );
        }

        return DB::transaction(function () use (
            $sale,
            $method,
            $actor,
            $amount,
            $reference,
        ): Payment {
            /** @var Sale $lockedSale */
            $lockedSale = Sale::query()
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $amountKobo = $this->money->toKobo($amount) ?? 0;

            if ($amountKobo <= 0) {
                throw new \InvalidArgumentException(
                    'Payment amount must be greater than zero.',
                );
            }

            $balanceDue = $lockedSale->balanceDueKobo();
            $overpaymentKobo = max(0, $amountKobo - $balanceDue);

            if ($overpaymentKobo > 0 && $lockedSale->customer_id === null) {
                throw new \DomainException(
                    'Payment amount exceeds the outstanding balance. '
                        .'Attach a customer to this sale to record the '
                        .'excess as store credit.',
                );
            }

            $payment = Payment::query()->create([
                'sale_id' => $lockedSale->getKey(),
                'payment_method_id' => $method->getKey(),
                'amount_kobo' => $amountKobo,
                'recorded_by_account_id' => $actor->getKey(),
                'reference' => $reference,
                'paid_at' => now(),
            ]);

            // The sale itself never shows as "paid" beyond its own total —
            // any excess becomes store credit on the customer record
            // instead (a negative balance_kobo = the business owes them).
            $newPaid = min(
                $lockedSale->grand_total_kobo,
                $lockedSale->paid_amount_kobo + $amountKobo,
            );

            $lockedSale->forceFill([
                'paid_amount_kobo' => $newPaid,
                'status' => SaleStatus::fromPayment(
                    $newPaid,
                    $lockedSale->grand_total_kobo,
                ),
            ])->save();

            if ($overpaymentKobo > 0) {
                /** @var Customer $customer */
                $customer = Customer::query()
                    ->whereKey($lockedSale->customer_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $customer->decrement('balance_kobo', $overpaymentKobo);
            }

            return $payment;
        });
    }
}
