<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Accounting\FinancialPostingReconciler;
use Illuminate\Console\Command;

final class ReconcileAccounting extends Command
{
    protected $signature = 'accounting:reconcile {--repair} {--fail-on-gap}';

    protected $description = 'Audit and optionally repair source-to-journal posting coverage idempotently.';

    public function handle(FinancialPostingReconciler $reconciler): int
    {
        if ((bool) $this->option('repair')) {
            $counts = $reconciler->repair();
            foreach ($counts as $source => $count) {
                $this->line("{$source}: {$count} scanned");
            }
        }

        $audit = $reconciler->audit();
        $this->table(
            ['posted', 'non-posting', 'gaps', 'invalid'],
            [[
                $audit['posted'],
                $audit['non_posting'],
                $audit['gaps'],
                $audit['invalid'],
            ]],
        );

        if ($audit['gaps'] > 0 || $audit['invalid'] > 0) {
            $this->error('Accounting reconciliation found unresolved posting gaps or invalid journals.');

            return (bool) $this->option('fail-on-gap')
                ? self::FAILURE
                : self::SUCCESS;
        }

        $this->info('Accounting reconciliation completed with zero differences.');

        return self::SUCCESS;
    }
}
