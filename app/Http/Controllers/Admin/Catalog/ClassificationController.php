<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Requests\Admin\Catalog\StoreClassificationRequest;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Services\Organisation\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

final readonly class ClassificationController
{
    public function __construct(private AuditLogger $audit) {}

    public function categories(): View
    {
        return view('admin.catalog.categories.index', [
            'records' => ProductCategory::query()
                ->withCount('products')
                ->orderBy('name')
                ->paginate((int) config(
                    'catalog.pagination.classifications',
                    50,
                )),
        ]);
    }

    public function storeCategory(
        StoreClassificationRequest $request,
    ): RedirectResponse {
        return $this->store(
            $request,
            ProductCategory::class,
            'category.created',
            'product_category',
            'Category created.',
        );
    }

    public function brands(): View
    {
        return view('admin.catalog.brands.index', [
            'records' => Brand::query()
                ->withCount('products')
                ->orderBy('name')
                ->paginate((int) config(
                    'catalog.pagination.classifications',
                    50,
                )),
        ]);
    }

    public function storeBrand(
        StoreClassificationRequest $request,
    ): RedirectResponse {
        return $this->store(
            $request,
            Brand::class,
            'brand.created',
            'brand',
            'Brand created.',
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function store(
        StoreClassificationRequest $request,
        string $modelClass,
        string $action,
        string $entityType,
        string $message,
    ): RedirectResponse {
        $record = $modelClass::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'slug' => Str::slug(
                $request->string('slug')->trim()->toString(),
            ),
            'description' => $request->filled('description')
                ? $request->string('description')->trim()->toString()
                : null,
            'status' => 'active',
        ]);

        $this->audit->record(
            $request,
            $action,
            $entityType,
            $record,
            after: $record->toArray(),
        );

        return back()->with('status', $message);
    }
}
