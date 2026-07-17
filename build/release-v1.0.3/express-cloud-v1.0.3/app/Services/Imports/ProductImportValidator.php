<?php

declare(strict_types=1);

namespace App\Services\Imports;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class ProductImportValidator
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{
     *   row_number:int,
     *   payload:array<string,mixed>,
     *   errors:list<string>,
     *   is_valid:bool
     * }>
     */
    public function validate(array $rows): array
    {
        $seenSkus = [];
        $seenBarcodes = [];
        $validated = [];

        foreach ($rows as $row) {
            $rowNumber = (int) ($row['_row_number'] ?? 0);
            $payload = $this->normalize($row);

            $validator = Validator::make($payload, [
                'sku' => ['required', 'string', 'max:100', 'alpha_dash'],
                'name' => ['required', 'string', 'max:200'],
                'barcode' => ['nullable', 'string', 'max:160'],
                'category' => ['required', 'string', 'max:140'],
                'brand' => ['nullable', 'string', 'max:140'],
                'supplier_code' => ['nullable', 'string', 'max:60', 'alpha_dash'],
                'supplier_name' => ['nullable', 'string', 'max:180'],
                'tax_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'default_price' => ['required', 'numeric', 'min:0'],
                'default_cost_price' => ['nullable', 'numeric', 'min:0'],
                'track_inventory' => [
                    'required',
                    Rule::in(['yes', 'no']),
                ],
                'description' => ['nullable', 'string', 'max:5000'],
                'status' => [
                    'required',
                    Rule::in(['active', 'inactive']),
                ],
            ]);

            $errors = array_values(array_merge(
                ...array_values($validator->errors()->toArray()),
            ));

            $sku = (string) ($payload['sku'] ?? '');
            $barcode = (string) ($payload['barcode'] ?? '');

            if ($sku !== '' && isset($seenSkus[$sku])) {
                $errors[] = sprintf(
                    'Duplicate SKU in workbook; first used on row %d.',
                    $seenSkus[$sku],
                );
            } elseif ($sku !== '') {
                $seenSkus[$sku] = $rowNumber;
            }

            if ($barcode !== '' && isset($seenBarcodes[$barcode])) {
                $errors[] = sprintf(
                    'Duplicate barcode in workbook; first used on row %d.',
                    $seenBarcodes[$barcode],
                );
            } elseif ($barcode !== '') {
                $seenBarcodes[$barcode] = $rowNumber;
            }

            $validated[] = [
                'row_number' => $rowNumber,
                'payload' => $payload,
                'errors' => $errors,
                'is_valid' => $errors === [],
            ];
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            if ($key === '_row_number') {
                continue;
            }

            $normalized[$key] = is_string($value)
                ? trim($value)
                : $value;
        }

        foreach (['sku', 'supplier_code'] as $key) {
            if (isset($normalized[$key])) {
                $normalized[$key] = mb_strtoupper(
                    (string) $normalized[$key],
                );
            }
        }

        foreach (['track_inventory', 'status'] as $key) {
            if (isset($normalized[$key])) {
                $normalized[$key] = mb_strtolower(
                    (string) $normalized[$key],
                );
            }
        }

        foreach ([
            'barcode',
            'brand',
            'supplier_code',
            'supplier_name',
            'tax_rate_percent',
            'default_cost_price',
            'description',
        ] as $nullable) {
            if (($normalized[$nullable] ?? '') === '') {
                $normalized[$nullable] = null;
            }
        }

        return $normalized;
    }
}
