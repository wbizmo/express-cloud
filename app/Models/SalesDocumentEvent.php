<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class SalesDocumentEvent extends Model
{
    use HasUlids;

    protected $table = 'sales_document_events';

    /** @var list<string> */
    protected $fillable = [
        'sale_id',
        'account_id',
        'event_type',
        'from_state',
        'to_state',
        'details',
        'memo',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
