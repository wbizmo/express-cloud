<?php

declare(strict_types=1);

namespace App\Actions\Commercial;

use App\Models\Account;
use App\Models\DiscountVoucher;
use App\Models\Sale;
use App\Models\VoucherRedemption;
use App\Services\Commercial\VoucherCalculator;
use Illuminate\Support\Facades\DB;

final readonly class ApplyVoucherToSale
{
    public function __construct(private VoucherCalculator $calculator) {}

    public function execute(
        Sale $sale,
        string $code,
        Account $actor,
    ): Sale {
        return DB::transaction(function () use (
            $sale,
            $code,
            $actor,
        ): Sale {
            /** @var Sale $lockedSale */
            $lockedSale = Sale::query()
                ->whereKey($sale->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->discount_voucher_id !== null) {
                throw new \DomainException(
                    'This sale already has a voucher.',
                );
            }

            /** @var DiscountVoucher $voucher */
            $voucher = DiscountVoucher::query()
                ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
                ->lockForUpdate()
                ->firstOrFail();

            $discount = $this->calculator->discountKobo(
                $voucher,
                $lockedSale->subtotal_kobo,
            );

            $newDiscount = $lockedSale->discount_amount_kobo + $discount;
            $newTotal = max(
                0,
                $lockedSale->subtotal_kobo
                    - $newDiscount
                    + $lockedSale->tax_amount_kobo,
            );

            if ($lockedSale->paid_amount_kobo > $newTotal) {
                throw new \DomainException(
                    'Voucher would reduce the total below payments already recorded.',
                );
            }

            $lockedSale->forceFill([
                'discount_voucher_id' => $voucher->getKey(),
                'discount_amount_kobo' => $newDiscount,
                'grand_total_kobo' => $newTotal,
            ])->save();

            VoucherRedemption::query()->create([
                'discount_voucher_id' => $voucher->getKey(),
                'sale_id' => $lockedSale->getKey(),
                'customer_id' => $lockedSale->customer_id,
                'redeemed_by_account_id' => $actor->getKey(),
                'discount_amount_kobo' => $discount,
                'redeemed_at' => now(),
            ]);

            $voucher->increment('usage_count');

            return $lockedSale->refresh();
        }, 3);
    }
}
