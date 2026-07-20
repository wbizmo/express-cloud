<?php

declare(strict_types=1);

namespace App\Services\Reports\Exports;

use App\Services\Documents\PdfRenderer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A single entry point for exporting any list-style report as CSV, Excel,
 * or PDF, from the same headings/rows shape. Every export produced through
 * this service is expected to include an "Initiated By" style column in the
 * caller's headings/rows when the underlying records have an actor, to
 * match the audit-log convention used elsewhere in the app.
 */
final readonly class TabularExport
{
    public function __construct(
        private CsvExport $csv,
        private PdfRenderer $pdf,
    ) {}

    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function csv(
        string $filename,
        array $headings,
        iterable $rows,
    ): StreamedResponse {
        return $this->csv->download($filename, $headings, $rows);
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function excel(
        string $filename,
        string $title,
        array $headings,
        iterable $rows,
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        foreach ($headings as $column => $heading) {
            $sheet->setCellValueByColumnAndRow($column + 1, 1, $heading);
        }

        $rowNumber = 2;

        foreach ($rows as $row) {
            foreach (array_values($row) as $column => $value) {
                $sheet->setCellValueByColumnAndRow(
                    $column + 1,
                    $rowNumber,
                    $value,
                );
            }
            $rowNumber++;
        }

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public function pdf(
        string $filename,
        string $title,
        array $headings,
        iterable $rows,
    ): Response {
        $content = $this->pdf->render('reports.exports.generic-table', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now(),
        ]);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
