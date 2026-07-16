<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Operations\AdminNotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AdminNotification extends Model
{
    use HasUlids;

    protected $table = 'admin_notifications';

    /** @var list<string> */
    protected $fillable = [
        'notification_type',
        'title',
        'message',
        'entity_type',
        'entity_id',
        'branch_id',
        'occurred_at',
        'read_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'notification_type' => AdminNotificationType::class,
            'occurred_at' => 'immutable_datetime',
            'read_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
