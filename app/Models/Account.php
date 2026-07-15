<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Authentication\AccountStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class Account extends Authenticatable
{
    use HasUlids;
    use Notifiable;

    protected $table = 'accounts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'first_name',
        'last_name',
        'email_encrypted',
        'login_key_encrypted',
        'login_key_blind_index',
        'login_key_version',
        'profile_picture_path',
        'status',
        'last_authenticated_at',
        'is_allowed_all_branches',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'email_encrypted',
        'login_key_encrypted',
        'login_key_blind_index',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'login_key_version' => 'integer',
            'last_authenticated_at' => 'immutable_datetime',
            'is_allowed_all_branches' => 'boolean',
        ];
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'account_role',
            'account_id',
            'role_id',
        )->withTimestamps();
    }

    /** @return BelongsToMany<Branch, $this> */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'account_branch',
            'account_id',
            'branch_id',
        )->withTimestamps();
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        return mb_strtoupper(
            mb_substr($this->first_name, 0, 1)
            .mb_substr($this->last_name, 0, 1),
        );
    }
}
