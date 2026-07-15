<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Commercial\DiscountValueType;
use App\Enums\Commercial\VoucherStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 */
final class DiscountVoucher extends Model
{
    use HasUlids;

    protected $fillable = [
        'code',
        'name',
        'value_type',
        'value',
        'minimum_sale_kobo',
        'maximum_discount_kobo',
        'usage_limit',
        'usage_count',
        'starts_at',
        'ends_at',
        'status',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'value_type' => DiscountValueType::class,
            'status' => VoucherStatus::class,
            'value' => 'integer',
            'minimum_sale_kobo' => 'integer',
            'maximum_discount_kobo' => 'integer',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }

    public function available(): bool
    {
        if ($this->status !== VoucherStatus::Active) {
            return false;
        }

        $startsAt = $this->starts_at;
        $endsAt = $this->ends_at;

        if ($startsAt !== null && $startsAt->isFuture()) {
            return false;
        }

        if ($endsAt !== null && $endsAt->isPast()) {
            return false;
        }

        return $this->usage_limit === null
            || $this->usage_count < $this->usage_limit;
    }
}
