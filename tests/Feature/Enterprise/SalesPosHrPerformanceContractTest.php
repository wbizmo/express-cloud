<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SalesPosHrPerformanceContractTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> */
    public static function contracts(): iterable
    {
        yield 'canonical sales engine' => [
            'app/Services/Sales/SalesWorkflowEngine.php',
            ['CreateSale $create', "'sales.document.convert'", 'FinancialPostingCoordinator', 'SalesDocumentEvent'],
        ];
        yield 'pos shift reconciliation' => [
            'app/Services/Pos/PosShiftService.php',
            ['opening_float_kobo', 'PosShiftTender::query()->updateOrCreate', 'variance_approval_threshold_kobo', 'Receipt reprints require an approved request.'],
        ];
        yield 'maker checker' => [
            'app/Services/Governance/AdminChangeService.php',
            ['The maker cannot approve their own administrative change.', 'deactivate', 'reactivate'],
        ];
        yield 'hr controls' => [
            'app/Services/Hr/HrAdministrationService.php',
            ['recordAttendance', 'PerformanceReview::query()->create', 'HR_PAYROLL_ENABLED'],
        ];
        yield 'streaming exports' => [
            'app/Services/Performance/StreamedCsvExport.php',
            ['streamDownload', 'lazyById', 'stream_chunk_size'],
        ];
        yield 'pagination audit' => [
            'app/Console/Commands/AuditPaginationPerformance.php',
            ['performance:audit', 'phase-9-pagination-query-audit.tsv', 'fail-on-violation'],
        ];
    }

    /** @param list<string> $needles */
    #[DataProvider('contracts')]
    public function test_sales_pos_hr_and_performance_contracts(string $path, array $needles): void
    {
        $source = file_get_contents(base_path($path));
        self::assertIsString($source);
        foreach ($needles as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }
}
