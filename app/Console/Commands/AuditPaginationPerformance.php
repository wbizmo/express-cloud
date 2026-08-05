<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class AuditPaginationPerformance extends Command
{
    protected $signature = 'performance:audit
        {--fail-on-violation : Fail when a Phase 6-9 list surface is unbounded}';

    protected $description = 'Audit Blade list surfaces, controller pagination and high-volume query contracts.';

    public function handle(Filesystem $files): int
    {
        $rows = [[
            'surface', 'kind', 'classification', 'loops', 'pagination', 'notes',
        ]];
        $violations = [];
        $phaseViews = [
            'admin/sales/workflows.blade.php',
            'admin/pos/workstation.blade.php',
            'admin/hr/index.blade.php',
            'admin/governance/changes.blade.php',
        ];

        foreach ($files->allFiles(resource_path('views')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = Str::after($file->getPathname(), resource_path('views').DIRECTORY_SEPARATOR);
            $source = $files->get($file->getPathname());
            $loops = substr_count($source, '@foreach') + substr_count($source, '@forelse');
            $hasTable = str_contains($source, '<table') || str_contains($source, 'role="table"');
            $hasPagination = str_contains($source, '->links()') || str_contains($source, '->hasPages()');
            $classification = $loops === 0
                ? 'not-a-list'
                : ($hasPagination ? 'paginated' : ($hasTable ? 'reviewed-bounded-or-static' : 'form-or-reference-list'));
            $rows[] = [
                $relative,
                'blade',
                $classification,
                (string) $loops,
                $hasPagination ? 'yes' : 'no',
                $hasTable ? 'table/list surface' : 'non-table surface',
            ];
            if (in_array($relative, $phaseViews, true) && $hasTable && ! $hasPagination) {
                $violations[] = $relative.' has a table without pagination controls.';
            }
        }

        $phaseControllers = [
            app_path('Http/Controllers/Admin/Sales/SalesWorkflowController.php'),
            app_path('Http/Controllers/Admin/Pos/PosWorkstationController.php'),
            app_path('Http/Controllers/Admin/Hr/HrAdministrationController.php'),
            app_path('Http/Controllers/Admin/Governance/AdminChangeController.php'),
        ];
        foreach ($files->allFiles(app_path('Http/Controllers')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $source = $files->get($file->getPathname());
            $gets = substr_count($source, '->get(');
            $pagination = substr_count($source, 'Paginate(') + substr_count($source, 'paginate(');
            if ($gets === 0 && $pagination === 0) {
                continue;
            }
            $relative = Str::after($file->getPathname(), app_path().DIRECTORY_SEPARATOR);
            $rows[] = [
                $relative,
                'controller',
                $pagination > 0 ? 'paginated-primary-list' : 'bounded-reference-or-review',
                (string) $gets,
                (string) $pagination,
                'get calls are reference/bounded only when primary list pagination exists',
            ];
            if (in_array($file->getPathname(), $phaseControllers, true) && $pagination === 0) {
                $violations[] = $relative.' lacks a paginated primary list.';
            }
        }

        $target = base_path('docs/hardening/phase-9-pagination-query-audit.tsv');
        $files->ensureDirectoryExists(dirname($target));
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            $this->error('Unable to create the pagination audit report.');

            return self::FAILURE;
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row, "\t");
        }
        fclose($handle);

        $this->info('Audited '.(count($rows) - 1).' Blade/controller list surfaces.');
        $this->line('Report: '.$target);
        if ($violations !== []) {
            foreach ($violations as $violation) {
                $this->error($violation);
            }
            if ($this->option('fail-on-violation')) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
