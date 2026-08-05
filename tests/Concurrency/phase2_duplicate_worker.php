<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Services\Operations\CommandBoundary;
use Illuminate\Contracts\Console\Kernel;

$database = $argv[1] ?? '';
$key = $argv[2] ?? '';
$sideEffect = $argv[3] ?? '';

if ($database === '' || $key === '' || $sideEffect === '') {
    fwrite(STDERR, "Usage: worker <database> <key> <side-effect-file>\n");
    exit(64);
}

foreach ([
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $database,
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

$autoload = dirname(__DIR__, 2).'/vendor/autoload.php';
if (! is_file($autoload)) {
    fwrite(STDERR, "Composer autoloader is missing: {$autoload}".PHP_EOL);
    exit(70);
}
require_once $autoload;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var CommandBoundary $commands */
$commands = $app->make(CommandBoundary::class);
$result = $commands->execute(
    'phase2.parallel-probe',
    $key,
    ['probe' => 'duplicate-retry'],
    null,
    null,
    static function () use ($sideEffect, $key): Branch {
        file_put_contents(
            $sideEffect,
            "executed\n",
            FILE_APPEND | LOCK_EX,
        );

        return Branch::query()->create([
            'name' => 'Phase 2 Parallel Probe',
            'code' => 'P2-'.strtoupper(substr(hash('sha256', $key), 0, 12)),
            'address' => 'Concurrency probe',
            'status' => 'active',
            'is_head_office' => false,
        ]);
    },
);

fwrite(STDOUT, (string) $result->getKey().PHP_EOL);
