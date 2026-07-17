<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class EndOfDayDigest extends Model
{
    use HasUlids;

    protected $table = 'end_of_day_digests';

    /** @var list<string> */
    protected $fillable = [
        'business_date',
        'status',
        'recipient_count',
        'summary',
        'started_at',
        'sent_at',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'immutable_date',
            'recipient_count' => 'integer',
            'summary' => 'array',
            'started_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
