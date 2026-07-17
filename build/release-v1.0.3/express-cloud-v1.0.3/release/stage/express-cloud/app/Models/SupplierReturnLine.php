<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierReturnLine extends Model
{
    use HasUlids;

    protected $table = 'supplier_return_lines';

    /** @var list<string> */
    protected $fillable = [
        'supplier_return_id',
        'product_id',
        'quantity_milliunits',
        'unit_cost_kobo',
        'line_total_kobo',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'line_total_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<SupplierReturn, $this> */
    public function supplierReturn(): BelongsTo
    {
        return $this->belongsTo(SupplierReturn::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
