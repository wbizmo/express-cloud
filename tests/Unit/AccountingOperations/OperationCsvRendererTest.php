<?php

declare(strict_types=1);

namespace Tests\Unit\AccountingOperations;

use App\Services\Documents\OperationCsvRenderer;
use App\Support\Documents\OperationReportData;
use PHPUnit\Framework\TestCase;

final class OperationCsvRendererTest extends TestCase
{
    public function test_spreadsheet_contains_rows_and_summary(): void
    {
        $report = new OperationReportData(
            title: 'Stock Transfer',
            reference: 'TRF-001',
            date: '2026-07-16',
            rows: [[
                'Product' => 'Frame A',
                'Quantity' => '5.000',
            ]],
            summary: [
                'Movement count' => 2,
            ],
        );

        $csv = (new OperationCsvRenderer)->render($report);

        self::assertStringContainsString('Frame A', $csv);
        self::assertStringContainsString(
            'Movement count',
            $csv,
        );
    }
}
