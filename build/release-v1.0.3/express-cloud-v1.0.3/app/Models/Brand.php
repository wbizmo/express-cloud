<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Catalog\RecordStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Brand extends Model
{
    use HasUlids;

    protected $table = 'brands';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
