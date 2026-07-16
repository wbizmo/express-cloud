<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PaymentMethod extends Model
{
    use HasUlids;

    protected $table = 'payment_methods';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'account_number_encrypted',
        'bank_name',
        'description',
        'is_system_default',
        'is_default_for_pos',
        'is_active',
        'created_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'is_system_default' => 'boolean',
            'is_default_for_pos' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function mayBeDeleted(): bool
    {
        return ! $this->is_system_default;
    }
}
