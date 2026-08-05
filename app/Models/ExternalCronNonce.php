<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ExternalCronNonce extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['nonce', 'timestamp', 'signature_hash', 'used_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'timestamp' => 'integer', 'used_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
