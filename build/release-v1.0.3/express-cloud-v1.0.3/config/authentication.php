<?php

declare(strict_types=1);

return [
    'access_key' => [
        'formatted_length' => 9,
        'raw_length' => 8,
        'search_minimum_characters' => 2,
        'search_result_limit' => 20,
    ],

    'throttle' => [
        'login_attempts_per_minute' => 10,
        'staff_searches_per_minute' => 30,
    ],

    'session' => [
        'inactivity_minutes' => 30,
        'maximum_concurrent_sessions' => 5,
    ],

    'profile_picture' => [
        'disk' => env('PROFILE_PICTURE_DISK', 'public'),
        'directory' => 'profile-pictures',
        'maximum_kilobytes' => 2048,
        'dimensions' => 512,
    ],
];
