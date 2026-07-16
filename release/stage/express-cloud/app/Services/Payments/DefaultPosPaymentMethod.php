<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

final class DefaultPosPaymentMethod
{
    public function set(PaymentMethod $method): void
    {
        if (! $method->is_active) {
            throw new \DomainException(
                'An inactive payment method cannot be the POS default.',
            );
        }

        DB::transaction(function () use ($method): void {
            PaymentMethod::query()
                ->where('is_default_for_pos', true)
                ->whereKeyNot($method->getKey())
                ->update(['is_default_for_pos' => false]);

            $method->forceFill([
                'is_default_for_pos' => true,
            ])->save();
        });
    }
}
