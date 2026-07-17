<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LowStockAlert extends Model
{
    use HasUlids;

    protected $table = 'low_stock_alerts';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'branch_id',
        'quantity_milliunits',
        'minimum_stock_milliunits',
        'opened_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'minimum_stock_milliunits' => 'integer',
            'opened_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
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
}
