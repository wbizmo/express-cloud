<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Catalog\RecordStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Supplier extends Model
{
    use HasUlids;

    protected $table = 'suppliers';

    /** @var list<string> */
    protected $fillable = [
        'supplier_code',
        'company_name',
        'contact_person',
        'category',
        'email_encrypted',
        'phone',
        'address',
        'tax_number_encrypted',
        'payment_terms_days',
        'credit_limit_kobo',
        'lead_time_days',
        'delivery_terms',
        'return_policy',
        'is_preferred',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
            'credit_limit_kobo' => 'integer',
            'lead_time_days' => 'integer',
            'is_preferred' => 'boolean',
            'status' => RecordStatus::class,
        ];
    }
}
