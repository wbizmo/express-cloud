<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\Imports\ImportStatus;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImport;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Services\Catalog\MoneyInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ProcessProductImport
{
    public function __construct(private MoneyInput $money) {}

    /**
     * @return array{
     *   created_products:int,
     *   updated_products:int,
     *   created_categories:int,
     *   created_brands:int,
     *   created_suppliers:int
     * }
     */
    public function execute(ProductImport $import): array
    {
        if ($import->status !== ImportStatus::Validated) {
            throw new \DomainException(
                'Only validated imports may be processed.',
            );
        }

        $import->forceFill([
            'status' => ImportStatus::Processing,
        ])->save();

        try {
            $counts = DB::transaction(function () use ($import): array {
                $counts = [
                    'created_products' => 0,
                    'updated_products' => 0,
                    'created_categories' => 0,
                    'created_brands' => 0,
                    'created_suppliers' => 0,
                ];

                foreach (
                    $import->rows()
                        ->where('is_valid', true)
                        ->orderBy('row_number')
                        ->cursor() as $row
                ) {
                    /** @var array<string, mixed> $payload */
                    $payload = is_array($row->payload)
                        ? $row->payload
                        : [];

                    $categoryName = trim(
                        (string) ($payload['category'] ?? ''),
                    );

                    if ($categoryName === '') {
                        throw new \DomainException(
                            'Validated import row is missing a category.',
                        );
                    }

                    $category = ProductCategory::query()
                        ->whereRaw(
                            'LOWER(name) = ?',
                            [mb_strtolower($categoryName)],
                        )
                        ->first();

                    if (! $category instanceof ProductCategory) {
                        $category = ProductCategory::query()->create([
                            'name' => $categoryName,
                            'slug' => $this->uniqueSlug(
                                ProductCategory::class,
                                $categoryName,
                            ),
                            'status' => 'active',
                        ]);
                        $counts['created_categories']++;
                    }

                    $brandName = trim(
                        (string) ($payload['brand'] ?? ''),
                    );
                    $brand = null;

                    if ($brandName !== '') {
                        $brand = Brand::query()
                            ->whereRaw(
                                'LOWER(name) = ?',
                                [mb_strtolower($brandName)],
                            )
                            ->first();

                        if (! $brand instanceof Brand) {
                            $brand = Brand::query()->create([
                                'name' => $brandName,
                                'slug' => $this->uniqueSlug(
                                    Brand::class,
                                    $brandName,
                                ),
                                'status' => 'active',
                            ]);
                            $counts['created_brands']++;
                        }
                    }

                    $supplierCode = trim(
                        (string) ($payload['supplier_code'] ?? ''),
                    );

                    if ($supplierCode !== '') {
                        $supplier = Supplier::query()
                            ->where('supplier_code', $supplierCode)
                            ->first();

                        if (! $supplier instanceof Supplier) {
                            $supplierName = trim(
                                (string) (
                                    $payload['supplier_name']
                                    ?? $supplierCode
                                ),
                            );

                            Supplier::query()->create([
                                'supplier_code' => $supplierCode,
                                'company_name' => $supplierName !== ''
                                    ? $supplierName
                                    : $supplierCode,
                                'status' => 'active',
                                'credit_limit_kobo' => 0,
                                'payment_terms_days' => 0,
                                'lead_time_days' => 0,
                                'is_preferred' => false,
                                'email_encrypted' => null,
                                'tax_number_encrypted' => null,
                            ]);
                            $counts['created_suppliers']++;
                        }
                    }

                    $taxRate = null;
                    $taxRatePercent = $payload['tax_rate_percent'] ?? null;

                    if ($taxRatePercent !== null && $taxRatePercent !== '') {
                        $basisPoints = (int) round(
                            (float) $taxRatePercent * 100,
                        );

                        $taxRate = TaxRate::query()
                            ->where('rate_basis_points', $basisPoints)
                            ->first();

                        if (! $taxRate instanceof TaxRate) {
                            $taxRate = TaxRate::query()->create([
                                'name' => number_format(
                                    $basisPoints / 100,
                                    2,
                                ).'% Tax',
                                'rate_basis_points' => $basisPoints,
                                'status' => 'active',
                                'is_default' => false,
                            ]);
                        }
                    }

                    $sku = trim((string) ($payload['sku'] ?? ''));

                    if ($sku === '') {
                        throw new \DomainException(
                            'Validated import row is missing a SKU.',
                        );
                    }

                    $existing = Product::query()
                        ->where('sku', $sku)
                        ->first();

                    $values = [
                        'name' => (string) ($payload['name'] ?? ''),
                        'sku' => $sku,
                        'barcode' => $payload['barcode'] ?? null,
                        'category_id' => $category->getKey(),
                        'brand_id' => $brand instanceof Brand
                            ? $brand->getKey()
                            : null,
                        'tax_rate_id' => $taxRate instanceof TaxRate
                            ? $taxRate->getKey()
                            : null,
                        'description' => $payload['description'] ?? null,
                        'track_inventory' => (
                            $payload['track_inventory'] ?? null
                        ) === 'yes',
                        'default_price_kobo' => $this->money->toKobo(
                            $payload['default_price'] ?? null,
                        ),
                        'default_cost_price_kobo' => $this->money->toKobo(
                            $payload['default_cost_price'] ?? null,
                        ),
                        'status' => (string) (
                            $payload['status'] ?? 'active'
                        ),
                    ];

                    if ($existing instanceof Product) {
                        $existing->forceFill($values)->save();
                        $counts['updated_products']++;
                    } else {
                        Product::query()->create($values);
                        $counts['created_products']++;
                    }

                    $row->forceFill(['processed_at' => now()])->save();
                }

                return $counts;
            });

            $import->forceFill([
                ...$counts,
                'status' => ImportStatus::Completed,
                'completed_at' => now(),
            ])->save();

            return $counts;
        } catch (\Throwable $exception) {
            $import->forceFill([
                'status' => ImportStatus::Failed,
                'summary' => [
                    ...(is_array($import->summary)
                        ? $import->summary
                        : []),
                    'failure' => 'The import transaction failed and was rolled back.',
                ],
                'completed_at' => now(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function uniqueSlug(
        string $modelClass,
        string $name,
    ): string {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
