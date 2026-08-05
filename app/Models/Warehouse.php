<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Warehouse extends Model
{
    use HasUlids;

    protected $fillable = [
        'branch_id', 'code', 'name', 'type', 'status', 'address',
        'is_default', 'allows_sales', 'allows_receipts',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'allows_sales' => 'boolean',
            'allows_receipts' => 'boolean',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return HasMany<WarehouseStockBalance, $this> */
    public function balances(): HasMany
    {
        return $this->hasMany(WarehouseStockBalance::class);
    }

    public function available(): bool
    {
        return $this->status === 'active';
    }
}
