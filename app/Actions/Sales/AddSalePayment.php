<?php

declare(strict_types=1);

namespace App\Actions\Sales;

use App\Enums\Sales\SaleStatus;
use App\Enums\Sales\SaleType;
use App\Models\Account;
use App\Models\OperationRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Services\Catalog\MoneyInput;
use App\Services\Operations\CommandBoundary;

final readonly class AddSalePayment
{
    public function __construct(
        private MoneyInput $money,
        private CommandBoundary $commands,
    ) {}

    public function execute(
        Sale $sale,
        PaymentMethod $method,
        Account $actor,
        mixed $amount,
        ?string $reference,
        string $idempotencyKey,
    ): Payment {
        $amountKobo = $this->money->toKobo($amount) ?? 0;

        if ($amountKobo <= 0) {
            throw new \InvalidArgumentException(
                'Payment amount must be greater than zero.',
            );
        }

        $result = $this->commands->execute(
            'sale.payment',
            $idempotencyKey,
            [
                'sale_id' => (string) $sale->getKey(),
                'payment_method_id' => (string) $method->getKey(),
                'amount_kobo' => $amountKobo,
                'reference' => $reference,
            ],
            $actor,
            (string) $sale->branch_id,
            function (OperationRequest $operation) use (
                $sale,
                $method,
                $actor,
                $amountKobo,
                $reference,
            ): Payment {
                /** @var Sale $lockedSale */
                $lockedSale = Sale::query()
                    ->whereKey($sale->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                /** @var PaymentMethod $lockedMethod */
                $lockedMethod = PaymentMethod::query()
                    ->whereKey($method->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedMethod->is_active) {
                    throw new \DomainException(
                        'Inactive payment methods cannot receive payments.',
                    );
                }

                if (
                    $lockedSale->sale_type === SaleType::Quote
                    || $lockedSale->status === SaleStatus::Cancelled
                ) {
                    throw new \DomainException(
                        'This sale cannot receive a payment.',
                    );
                }

                $balanceDue = $lockedSale->balanceDueKobo();

                if ($balanceDue <= 0) {
                    throw new \DomainException(
                        'This sale has no outstanding balance.',
                    );
                }

                if ($amountKobo > $balanceDue) {
                    throw new \DomainException(
                        'Payment amount exceeds the outstanding balance. Overpayments are not permitted.',
                    );
                }

                $payment = Payment::query()->create([
                    'sale_id' => $lockedSale->getKey(),
                    'payment_method_id' => $lockedMethod->getKey(),
                    'amount_kobo' => $amountKobo,
                    'recorded_by_account_id' => $actor->getKey(),
                    'reference' => $reference,
                    'operation_request_id' => $operation->getKey(),
                    'operation_sequence' => 1,
                    'paid_at' => now(),
                ]);
                $newPaid = $lockedSale->paid_amount_kobo + $amountKobo;

                $lockedSale->forceFill([
                    'paid_amount_kobo' => $newPaid,
                    'status' => SaleStatus::fromPayment(
                        $newPaid,
                        $lockedSale->grand_total_kobo,
                    ),
                ])->save();

                return $payment;
            },
        );

        if (! $result instanceof Payment) {
            throw new \LogicException(
                'The payment command returned an invalid result.',
            );
        }

        return $result;
    }
}
