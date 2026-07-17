<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Catalog\RecordStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    use HasUlids;

    protected $table = 'products';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'tax_rate_id',
        'description',
        'image_path',
        'track_inventory',
        'default_price_kobo',
        'default_cost_price_kobo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'default_price_kobo' => 'integer',
            'default_cost_price_kobo' => 'integer',
            'status' => RecordStatus::class,
        ];
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<TaxRate, $this> */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /** @return HasMany<ProductBranchStock, $this> */
    public function branchStock(): HasMany
    {
        return $this->hasMany(ProductBranchStock::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<ProductBranchPrice, $this> */
    public function branchPrices(): HasMany
    {
        return $this->hasMany(ProductBranchPrice::class);
    }

    public function inventoryLabel(): string
    {
        return $this->track_inventory
            ? 'Tracked inventory'
            : 'Untracked item';
    }
}
