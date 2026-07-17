<?php

declare(strict_types=1);

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'backups'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'include_uploads' => (bool) env('BACKUP_INCLUDE_UPLOADS', true),
    'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'pg_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
];
