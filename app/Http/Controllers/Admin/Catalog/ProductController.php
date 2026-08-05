<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Catalog\CreateProduct;
use App\Actions\Catalog\StoreProductImage;
use App\Enums\Catalog\RecordStatus;
use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Http\Requests\Admin\Catalog\UpdateProductRequest;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ProductController
{
    public function __construct(
        private CreateProduct $createProduct,
        private StoreProductImage $storeProductImage,
        private AuditLogger $audit,
        private MoneyInput $money,
    ) {}

    public function index(): View
    {
        return view('admin.catalog.products.index', [
            'products' => Product::query()
                ->with(['category:id,name', 'brand:id,name'])
                ->orderBy('name')
                ->paginate((int) config('catalog.pagination.products', 30)),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->createProduct->execute($request);

        if ($request->hasFile('image')) {
            $this->storeProductImage->execute($product, $request->file('image'));
        }

        $this->audit->record($request, 'product.created', 'product', $product, after: [
            'name' => $product->name,
            'sku' => $product->sku,
            'track_inventory' => $product->track_inventory,
            'default_price_kobo' => $product->default_price_kobo,
            'status' => $product->status instanceof RecordStatus ? $product->status->value : (string) $product->status,
        ]);

        return redirect()->route('admin.catalog.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('branchPrices');

        return view('admin.catalog.products.edit', [
            ...$this->formData(),
            'product' => $product,
            'branchPriceMap' => $product->branchPrices->mapWithKeys(
                static fn (ProductBranchPrice $price): array => [
                    (string) $price->branch_id => (int) $price->price_kobo,
                ],
            ),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $before = $product->only([
            'name', 'sku', 'barcode', 'category_id', 'brand_id', 'tax_rate_id',
            'description', 'track_inventory', 'default_price_kobo',
            'default_cost_price_kobo', 'status',
        ]);

        DB::transaction(function () use ($request, $product): void {
            $product->update([
                'name' => $request->string('name')->trim()->toString(),
                'sku' => Str::upper($request->string('sku')->trim()->toString()),
                'barcode' => $request->filled('barcode') ? $request->string('barcode')->trim()->toString() : null,
                'category_id' => $request->string('category_id')->toString(),
                'brand_id' => $request->filled('brand_id') ? $request->string('brand_id')->toString() : null,
                'tax_rate_id' => $request->filled('tax_rate_id') ? $request->string('tax_rate_id')->toString() : null,
                'description' => $request->filled('description') ? $request->string('description')->trim()->toString() : null,
                'track_inventory' => $request->boolean('track_inventory'),
                'default_price_kobo' => $this->money->toKobo($request->input('default_price')),
                'default_cost_price_kobo' => $this->money->toKobo($request->input('default_cost_price')),
                'status' => $request->string('status')->toString(),
            ]);

            foreach ($request->array('branch_prices') as $row) {
                if (! is_array($row) || empty($row['branch_id'])) {
                    continue;
                }

                if (! isset($row['price']) || $row['price'] === '' || $row['price'] === null) {
                    ProductBranchPrice::query()
                        ->where('product_id', $product->getKey())
                        ->where('branch_id', (string) $row['branch_id'])
                        ->delete();

                    continue;
                }

                ProductBranchPrice::query()->updateOrCreate(
                    [
                        'product_id' => $product->getKey(),
                        'branch_id' => (string) $row['branch_id'],
                    ],
                    ['price_kobo' => $this->money->toKobo($row['price'])],
                );
            }
        });

        if ($request->hasFile('image')) {
            $this->storeProductImage->execute($product, $request->file('image'));
        }

        $this->audit->record($request, 'product.updated', 'product', $product, before: $before, after: $product->fresh()->toArray());

        return redirect()->route('admin.catalog.products.index')->with('status', 'Product updated.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'categories' => ProductCategory::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'taxRates' => TaxRate::query()->where('status', 'active')->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'rate_basis_points']),
            'branches' => Branch::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
