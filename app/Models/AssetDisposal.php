<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AssetDisposal extends Model
{
    use HasUlids;

    protected $fillable = [
        'fixed_asset_id', 'journal_entry_id', 'disposed_by_account_id',
        'disposed_on', 'proceeds_kobo', 'net_book_value_kobo',
        'gain_loss_kobo', 'method', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'disposed_on' => 'immutable_date',
            'proceeds_kobo' => 'integer',
            'net_book_value_kobo' => 'integer',
            'gain_loss_kobo' => 'integer',
        ];
    }
}
