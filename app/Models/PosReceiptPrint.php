<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosReceiptPrint extends Model
{
    use HasUlids;

    protected $table = 'pos_receipt_prints';

    /** @var list<string> */
    protected $fillable = [
        'sale_id',
        'pos_shift_id',
        'printed_by_account_id',
        'approval_request_id',
        'format',
        'copy_number',
        'is_reprint',
        'reason',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'copy_number' => 'integer',
            'is_reprint' => 'boolean',
            'printed_at' => 'immutable_datetime',
        ];
    }
}
