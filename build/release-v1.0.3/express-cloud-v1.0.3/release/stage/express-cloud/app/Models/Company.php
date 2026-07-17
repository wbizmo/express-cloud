<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Company extends Model
{
    use HasUlids;

    protected $table = 'companies';

    /** @var list<string> */
    protected $fillable = [
        'legal_name',
        'trading_name',
        'head_office_address',
        'phone',
        'email_encrypted',
        'logo_path',
        'timezone',
        'is_configured',
    ];

    protected function casts(): array
    {
        return [
            'is_configured' => 'boolean',
        ];
    }
}
