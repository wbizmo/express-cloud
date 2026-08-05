<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ReorderRule extends Model
{
    use HasUlids;

    protected $fillable = [
        'warehouse_id', 'product_id', 'product_variant_id', 'identity_hash',
        'reorder_point_milliunits', 'target_stock_milliunits',
        'lead_time_days', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reorder_point_milliunits' => 'integer',
            'target_stock_milliunits' => 'integer',
            'lead_time_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
