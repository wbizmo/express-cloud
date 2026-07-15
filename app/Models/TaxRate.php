<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Catalog\RecordStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TaxRate extends Model
{
    use HasUlids;

    protected $table = 'tax_rates';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'rate_basis_points',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'rate_basis_points' => 'integer',
            'status' => RecordStatus::class,
            'is_default' => 'boolean',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function percentage(): string
    {
        return number_format($this->rate_basis_points / 100, 2);
    }
}
