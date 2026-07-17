<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierBillLine extends Model
{
    use HasUlids;

    protected $table = 'supplier_bill_lines';

    /** @var list<string> */
    protected $fillable = [
        'supplier_bill_id',
        'product_id',
        'description',
        'quantity_milliunits',
        'unit_cost_kobo',
        'tax_rate_basis_points',
        'line_subtotal_kobo',
        'tax_kobo',
        'line_total_kobo',
    ];

    protected function casts(): array
    {
        return [
            'quantity_milliunits' => 'integer',
            'unit_cost_kobo' => 'integer',
            'tax_rate_basis_points' => 'integer',
            'line_subtotal_kobo' => 'integer',
            'tax_kobo' => 'integer',
            'line_total_kobo' => 'integer',
        ];
    }

    /** @return BelongsTo<SupplierBill, $this> */
    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
