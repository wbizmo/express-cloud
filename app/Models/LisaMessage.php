<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class LisaMessage extends Model
{
    use HasUlids;

    protected $fillable = ['conversation_id', 'account_id', 'role', 'content', 'context_snapshot', 'response_time_ms'];

    protected function casts(): array
    {
        return ['context_snapshot' => 'array'];
    }
}
