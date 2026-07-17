<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Organisation\BranchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Branch extends Model
{
    use HasUlids;

    protected $table = 'branches';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'status',
        'is_head_office',
    ];

    protected function casts(): array
    {
        return [
            'status' => BranchStatus::class,
            'is_head_office' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Account, $this> */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(
            Account::class,
            'account_branch',
            'branch_id',
            'account_id',
        )->withTimestamps();
    }
}
