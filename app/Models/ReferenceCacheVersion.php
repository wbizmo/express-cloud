<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ReferenceCacheVersion extends Model
{
    protected $table = 'reference_cache_versions';

    protected $primaryKey = 'namespace';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = ['namespace', 'version', 'invalidated_at'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'invalidated_at' => 'immutable_datetime'];
    }
}
