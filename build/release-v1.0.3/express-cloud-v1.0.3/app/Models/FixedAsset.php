<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountingOperations\FixedAssetStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class FixedAsset extends Model
{
    use HasUlids;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'branch_id',
        'custodian_account_id',
        'acquired_at',
        'cost_kobo',
        'salvage_value_kobo',
        'useful_life_months',
        'serial_number',
        'location',
        'status',
        'notes',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'immutable_date',
            'cost_kobo' => 'integer',
            'salvage_value_kobo' => 'integer',
            'useful_life_months' => 'integer',
            'status' => FixedAssetStatus::class,
        ];
    }

    public function monthlyDepreciationKobo(): int
    {
        $depreciable = max(
            0,
            $this->cost_kobo - $this->salvage_value_kobo,
        );

        return $this->useful_life_months > 0
            ? intdiv($depreciable, $this->useful_life_months)
            : 0;
    }
}
