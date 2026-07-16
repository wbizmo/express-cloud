<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $revoked_at
 */
final class ApiToken extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'token_prefix',
        'token_hash',
        'abilities',
        'created_by_account_id',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'created_by_account_id',
        );
    }

    public function active(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        $expiresAt = $this->expires_at;

        return $expiresAt === null
            || $expiresAt->isFuture();
    }

    public function allows(string $ability): bool
    {
        /** @var list<string> $abilities */
        $abilities = $this->abilities ?? [];

        return in_array('*', $abilities, true)
            || in_array($ability, $abilities, true);
    }
}
