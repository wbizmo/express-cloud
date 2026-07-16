<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AssetDepreciationPosting extends Model
{
    use HasUlids;

    protected $fillable = [
        'fixed_asset_id',
        'journal_entry_id',
        'period_end',
        'amount_kobo',
    ];

    protected function casts(): array
    {
        return [
            'period_end' => 'immutable_date',
            'amount_kobo' => 'integer',
        ];
    }
}
