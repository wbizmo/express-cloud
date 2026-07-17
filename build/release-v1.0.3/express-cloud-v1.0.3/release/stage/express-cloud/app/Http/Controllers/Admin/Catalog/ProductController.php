<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Catalog\CreateProduct;
use App\Actions\Catalog\StoreProductImage;
use App\Enums\Catalog\RecordStatus;
use App\Http\Requests\Admin\Catalog\StoreProductRequest;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class ProductController
{
    public function __construct(
        private CreateProduct $createProduct,
        private StoreProductImage $storeProductImage,
        private AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.catalog.products.index', [
            'products' => Product::query()
                ->with(['category:id,name', 'brand:id,name'])
                ->orderBy('name')
                ->paginate((int) config(
                    'catalog.pagination.products',
                    30,
                )),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.create', [
            'categories' => ProductCategory::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'brands' => Brand::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'taxRates' => TaxRate::query()
                ->where('status', 'active')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'rate_basis_points']),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StoreProductRequest $request,
    ): RedirectResponse {
        $product = $this->createProduct->execute($request);

        if ($request->hasFile('image')) {
            $this->storeProductImage->execute(
                $product,
                $request->file('image'),
            );
        }

        $this->audit->record(
            $request,
            'product.created',
            'product',
            $product,
            after: [
                'name' => $product->name,
                'sku' => $product->sku,
                'track_inventory' => $product->track_inventory,
                'default_price_kobo' => $product->default_price_kobo,
                'status' => $product->status instanceof RecordStatus
                    ? $product->status->value
                    : (string) $product->status,
            ],
        );

        return redirect()
            ->route('admin.catalog.products.index')
            ->with('status', 'Product created.');
    }
}
