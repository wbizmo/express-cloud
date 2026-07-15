<?php

declare(strict_types=1);

namespace Tests\Unit\Imports;

use App\Services\Imports\ProductImportValidator;
use Tests\TestCase;

final class ProductImportValidatorTest extends TestCase
{
    public function test_valid_row_is_normalized(): void
    {
        $result = (new ProductImportValidator)->validate([[
            '_row_number' => 2,
            'sku' => ' frame-1 ',
            'name' => 'Classic Frame',
            'barcode' => '',
            'category' => 'Frames',
            'brand' => '',
            'supplier_code' => ' sup-1 ',
            'supplier_name' => 'Supplier',
            'tax_rate_percent' => '7.5',
            'default_price' => '25000',
            'default_cost_price' => '15000',
            'track_inventory' => 'YES',
            'description' => '',
            'status' => 'ACTIVE',
        ]]);

        self::assertTrue($result[0]['is_valid']);
        self::assertSame('FRAME-1', $result[0]['payload']['sku']);
        self::assertSame('SUP-1', $result[0]['payload']['supplier_code']);
        self::assertSame('yes', $result[0]['payload']['track_inventory']);
        self::assertNull($result[0]['payload']['barcode']);
    }

    public function test_duplicate_sku_rows_are_rejected(): void
    {
        $rows = [];

        foreach ([2, 3] as $rowNumber) {
            $rows[] = [
                '_row_number' => $rowNumber,
                'sku' => 'SKU-1',
                'name' => 'Product',
                'barcode' => null,
                'category' => 'Category',
                'brand' => null,
                'supplier_code' => null,
                'supplier_name' => null,
                'tax_rate_percent' => null,
                'default_price' => '100',
                'default_cost_price' => null,
                'track_inventory' => 'yes',
                'description' => null,
                'status' => 'active',
            ];
        }

        $result = (new ProductImportValidator)->validate($rows);

        self::assertTrue($result[0]['is_valid']);
        self::assertFalse($result[1]['is_valid']);
        self::assertStringContainsString(
            'Duplicate SKU',
            $result[1]['errors'][0],
        );
    }
}
