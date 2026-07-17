<?php

declare(strict_types=1);

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductImportTemplateExport
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'sku',
        'name',
        'barcode',
        'category',
        'brand',
        'supplier_code',
        'supplier_name',
        'tax_rate_percent',
        'default_price',
        'default_cost_price',
        'track_inventory',
        'description',
        'status',
    ];

    public function download(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'express-cloud-products-');

        if ($path === false) {
            throw new \RuntimeException('Unable to create template file.');
        }

        $xlsxPath = $path.'.xlsx';
        @unlink($path);

        $spreadsheet = new Spreadsheet;
        $products = $spreadsheet->getActiveSheet();
        $products->setTitle('Products');

        $this->writeProductsSheet($products);
        $this->writeInstructionsSheet(
            $spreadsheet->createSheet()->setTitle('Instructions'),
        );
        $this->writeReferenceSheet(
            $spreadsheet->createSheet()->setTitle('Reference'),
        );

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download(
                $xlsxPath,
                'express-cloud-product-import-sample.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    private function writeProductsSheet(Worksheet $sheet): void
    {
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->fromArray([
            'FRAME-001',
            'Classic Black Frame',
            '0123456789012',
            'Frames',
            'Zivora Studio',
            'SUP-001',
            'Example Frames Limited',
            '7.50',
            '25000.00',
            '15000.00',
            'yes',
            'Black wooden picture frame',
            'active',
        ], null, 'A2');

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M1');
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B1F3A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $booleanValidation = new DataValidation;
        $booleanValidation->setType(DataValidation::TYPE_LIST);
        $booleanValidation->setAllowBlank(false);
        $booleanValidation->setShowDropDown(true);
        $booleanValidation->setFormula1('"yes,no"');

        $statusValidation = clone $booleanValidation;
        $statusValidation->setFormula1('"active,inactive"');

        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('K'.$row)->setDataValidation(
                clone $booleanValidation,
            );
            $sheet->getCell('M'.$row)->setDataValidation(
                clone $statusValidation,
            );
        }
    }

    private function writeInstructionsSheet(Worksheet $sheet): void
    {
        $rows = [
            ['Express Cloud Product Import Instructions'],
            [''],
            ['File type', 'Excel .xlsx only'],
            ['Required columns', 'sku, name, category, default_price, track_inventory'],
            ['SKU', 'Unique uppercase product identifier'],
            ['Category and brand', 'Existing values are matched; missing values may be created'],
            ['Supplier', 'supplier_code is authoritative when provided'],
            ['Tax', 'Enter a percentage such as 7.50'],
            ['Money', 'Enter naira values without currency symbols'],
            ['Track inventory', 'Use yes or no'],
            ['Status', 'Use active or inactive'],
            ['Duplicates', 'Existing SKU updates the product; duplicate SKU rows in one file fail validation'],
            ['Stock quantity', 'Not imported here. Opening stock is handled by inventory workflows'],
            ['Images', 'Not imported from workbook URLs'],
        ];

        $sheet->fromArray($rows, null, 'A1');
        $sheet->getColumnDimension('A')->setWidth(26);
        $sheet->getColumnDimension('B')->setWidth(90);
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
    }

    private function writeReferenceSheet(Worksheet $sheet): void
    {
        $sheet->fromArray([
            ['Allowed track_inventory values', 'yes', 'no'],
            ['Allowed status values', 'active', 'inactive'],
            ['Maximum preview rows', '50'],
            ['Import behavior', 'Validate first, then confirm import'],
        ], null, 'A1');

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
