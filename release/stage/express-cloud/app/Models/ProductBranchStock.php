<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductBranchStock extends Model
{
    use HasUlids;

    protected $table = 'product_branch_stock';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity_milliunits',
        'minimum_stock_milliunits',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'minimum_stock_milliunits' => 'integer',
            'last_movement_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity_milliunits
            <= $this->minimum_stock_milliunits;
    }
}
