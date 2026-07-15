<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Services\Catalog\MoneyInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateProduct
{
    public function __construct(private MoneyInput $money) {}

    public function execute(StoreProductRequest $request): Product
    {
        return DB::transaction(function () use ($request): Product {
            $product = Product::query()->create([
                'name' => $request->string('name')->trim()->toString(),
                'sku' => Str::upper(
                    $request->string('sku')->trim()->toString(),
                ),
                'barcode' => $request->filled('barcode')
                    ? $request->string('barcode')->trim()->toString()
                    : null,
                'category_id' => $request->string('category_id')->toString(),
                'brand_id' => $request->filled('brand_id')
                    ? $request->string('brand_id')->toString()
                    : null,
                'tax_rate_id' => $request->filled('tax_rate_id')
                    ? $request->string('tax_rate_id')->toString()
                    : null,
                'description' => $request->filled('description')
                    ? $request->string('description')->trim()->toString()
                    : null,
                'track_inventory' => $request->boolean('track_inventory'),
                'default_price_kobo' => $this->money->toKobo(
                    $request->input('default_price'),
                ),
                'default_cost_price_kobo' => $this->money->toKobo(
                    $request->input('default_cost_price'),
                ),
                'status' => 'active',
            ]);

            foreach ($request->array('branch_prices') as $row) {
                if (! is_array($row) || empty($row['price'])) {
                    continue;
                }

                ProductBranchPrice::query()->create([
                    'product_id' => $product->getKey(),
                    'branch_id' => (string) $row['branch_id'],
                    'price_kobo' => $this->money->toKobo($row['price']),
                ]);
            }

            return $product;
        });
    }
}
