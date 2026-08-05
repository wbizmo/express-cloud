<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryBatch extends Model
{
    use HasUlids;

    protected $fillable = [
        'product_id', 'product_variant_id', 'warehouse_id',
        'batch_number', 'manufactured_on', 'expires_on', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_on' => 'immutable_date',
            'expires_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function expired(): bool
    {
        $expiresOn = $this->getAttribute('expires_on');

        return $expiresOn instanceof CarbonInterface && $expiresOn->isPast();
    }
}
