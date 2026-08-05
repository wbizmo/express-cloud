<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class JobRole extends Model
{
    use HasUlids;

    protected $table = 'job_roles';

    /** @var list<string> */
    protected $fillable = [
        'code',
        'title',
        'department_id',
        'description',
        'status',
    ];
}
