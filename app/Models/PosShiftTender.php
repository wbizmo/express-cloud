<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PosShiftTender extends Model
{
    use HasUlids;

    protected $table = 'pos_shift_tenders';

    /** @var list<string> */
    protected $fillable = [
        'pos_shift_id',
        'payment_method_id',
        'expected_amount_kobo',
        'counted_amount_kobo',
        'variance_kobo',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount_kobo' => 'integer',
            'counted_amount_kobo' => 'integer',
            'variance_kobo' => 'integer',
        ];
    }
}
