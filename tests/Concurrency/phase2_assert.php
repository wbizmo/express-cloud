<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\OutboxEvent;
use Illuminate\Contracts\Console\Kernel;

$database = $argv[1] ?? '';
$sideEffect = $argv[2] ?? '';

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
$lines = is_file($sideEffect)
    ? file($sideEffect, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
    : [];
$failures = [];

if (count($lines ?: []) !== 1) {
    $failures[] = 'callback executions='.count($lines ?: []);
}

if (OperationRequest::query()->where('scope', 'phase2.parallel-probe')->count() !== 1) {
    $failures[] = 'operation rows are not exactly one';
}

if (OperationRequest::query()->where('scope', 'phase2.parallel-probe')->where('status', 'succeeded')->count() !== 1) {
    $failures[] = 'operation did not reach succeeded exactly once';
}

if (OutboxEvent::query()->where('event_type', 'phase2.parallel-probe.completed')->count() !== 1) {
    $failures[] = 'outbox events are not exactly one';
}

if (Branch::query()->where('name', 'Phase 2 Parallel Probe')->count() !== 1) {
    $failures[] = 'business results are not exactly one';
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Phase 2 parallel duplicate-retry probe passed.\n");
