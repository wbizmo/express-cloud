<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class BusinessSetting extends Model
{
    protected $table = 'business_settings';

    protected $primaryKey = 'singleton_key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'singleton_key',
        'business_name',
        'business_logo_path',
        'head_office_address',
        'end_of_day_digest_time',
        'session_inactivity_minutes',
    ];

    protected function casts(): array
    {
        return [
            'session_inactivity_minutes' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['singleton_key' => 'default'],
            [
                'business_name' => 'Express Cloud',
                'head_office_address' => '',
                'end_of_day_digest_time' => '21:00:00',
                'session_inactivity_minutes' => 20,
            ],
        );
    }
}
