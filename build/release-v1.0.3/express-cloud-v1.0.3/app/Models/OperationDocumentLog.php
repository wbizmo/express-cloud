<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class OperationDocumentLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'operation_type',
        'operation_id',
        'format',
        'document_hash',
        'generated_by_account_id',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'immutable_datetime',
        ];
    }
}
