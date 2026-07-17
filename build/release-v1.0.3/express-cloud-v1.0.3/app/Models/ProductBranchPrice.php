<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductBranchPrice extends Model
{
    use HasUlids;

    protected $table = 'product_branch_prices';

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'branch_id',
        'price_kobo',
    ];

    protected function casts(): array
    {
        return [
            'price_kobo' => 'integer',
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
