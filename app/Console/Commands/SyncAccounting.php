<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class SyncAccounting extends Command
{
    protected $signature = 'accounting:sync';

    protected $description = 'Deprecated alias for accounting:reconcile --repair --fail-on-gap.';

    public function handle(): int
    {
        $this->warn('accounting:sync is deprecated; running accounting:reconcile --repair.');

        return $this->call('accounting:reconcile', [
            '--repair' => true,
            '--fail-on-gap' => true,
        ]);
    }
}
