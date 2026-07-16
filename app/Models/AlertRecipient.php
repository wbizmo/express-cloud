<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AlertRecipient extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $table = 'alert_recipients';

    /** @var list<string> */
    protected $fillable = [
        'email',
        'label',
        'is_active',
        'added_by_account_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'added_by_account_id',
        );
    }
}
