<?php

declare(strict_types=1);

return [
    'payroll_enabled' => (bool) env('HR_PAYROLL_ENABLED', false),
    'maker_checker_enabled' => (bool) env('HR_MAKER_CHECKER_ENABLED', true),
    'attendance_grace_minutes' => (int) env('HR_ATTENDANCE_GRACE_MINUTES', 10),
    'page_size' => (int) env('HR_PAGE_SIZE', 10),
];
