<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountingOperations\StandaloneReceiptStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class StandaloneReceipt extends Model
{
    use HasUlids;

    protected $fillable = [
        'receipt_number',
        'branch_id',
        'customer_id',
        'payment_method_id',
        'received_by_account_id',
        'payer_name',
        'payer_phone',
        'amount_kobo',
        'reference',
        'purpose',
        'notes',
        'status',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'status' => StandaloneReceiptStatus::class,
            'received_at' => 'immutable_datetime',
        ];
    }
}
