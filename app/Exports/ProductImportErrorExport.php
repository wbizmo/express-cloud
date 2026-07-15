<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\ProductImport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ProductImportErrorExport
{
    public function create(
        ProductImport $import,
        string $absolutePath,
    ): void {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Errors');

        $headers = array_merge(
            ['row_number'],
            ProductImportTemplateExport::HEADERS,
            ['errors'],
        );

        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;

        foreach (
            $import->rows()
                ->where('is_valid', false)
                ->orderBy('row_number')
                ->cursor() as $row
        ) {
            /** @var array<string, mixed> $payload */
            $payload = is_array($row->payload)
                ? $row->payload
                : [];

            $errors = [];

            if (is_array($row->errors)) {
                foreach ($row->errors as $error) {
                    $errors[] = (string) $error;
                }
            }

            $values = [$row->row_number];

            foreach (ProductImportTemplateExport::HEADERS as $header) {
                $values[] = array_key_exists($header, $payload)
                    ? $payload[$header]
                    : null;
            }

            $values[] = implode(' | ', $errors);
            $sheet->fromArray($values, null, 'A'.$rowIndex);
            $rowIndex++;
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:O1');
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B42318'],
            ],
        ]);

        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($absolutePath);
        $spreadsheet->disconnectWorksheets();
    }
}
