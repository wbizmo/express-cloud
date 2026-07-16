<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Exports\ProductImportTemplateExport;
use PhpOffice\PhpSpreadsheet\IOFactory;

final class ProductWorkbookReader
{
    /**
     * @return list<array<string, mixed>>
     */
    public function read(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getSheetByName('Products')
            ?? $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            return [];
        }

        $headers = array_map(
            static fn (mixed $value): string => mb_strtolower(
                trim((string) $value),
            ),
            array_shift($rows),
        );

        $this->assertHeaders($headers);

        $records = [];

        foreach ($rows as $index => $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $payload = [];

            foreach (ProductImportTemplateExport::HEADERS as $column => $header) {
                $payload[$header] = $row[$column] ?? null;
            }

            $payload['_row_number'] = $index + 2;
            $records[] = $payload;
        }

        return $records;
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertHeaders(array $headers): void
    {
        $missing = array_values(array_diff(
            ProductImportTemplateExport::HEADERS,
            $headers,
        ));

        if ($missing !== []) {
            throw new \InvalidArgumentException(
                'Missing required workbook columns: '.implode(', ', $missing),
            );
        }
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
