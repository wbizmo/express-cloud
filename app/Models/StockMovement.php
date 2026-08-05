<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Inventory\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockMovement extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'stock_movements';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'branch_id',
        'account_id',
        'movement_type',
        'quantity_delta_milliunits',
        'balance_after_milliunits',
        'unit_cost_kobo',
        'reference_type',
        'reference_id',
        'correlation_id',
        'reason_code',
        'note',
        'operation_request_id',
        'operation_sequence',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity_delta_milliunits' => 'integer',
            'balance_after_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'operation_sequence' => 'integer',
            'occurred_at' => 'immutable_datetime',
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

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
