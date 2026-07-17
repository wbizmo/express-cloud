<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Permission extends Model
{
    use HasUlids;

    protected $table = 'permissions';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'permission_id',
            'role_id',
        )->withTimestamps();
    }
}
