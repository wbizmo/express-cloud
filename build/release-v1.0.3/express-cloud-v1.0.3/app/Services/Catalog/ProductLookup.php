<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Organisation\BranchAccess;
use Illuminate\Support\Collection;

final readonly class ProductLookup
{
    public function __construct(private BranchAccess $branches) {}

    /** @return Collection<int, array<string, mixed>> */
    public function search(Account $actor, Branch $branch, string $term, int $limit = 20): Collection
    {
        $this->branches->enforce($actor, $branch);
        $term = trim($term);
        abort_if($term === '', 422);

        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term): void {
                $query->where('barcode', $term)
                    ->orWhere('sku', $term)
                    ->orWhere('name', 'like', '%'.addcslashes($term, '%_\\').'%');
            })
            ->with([
                'branchStock' => fn ($query) => $query->where('branch_id', $branch->getKey()),
                'branchPrices' => fn ($query) => $query->where('branch_id', $branch->getKey()),
            ])
            ->orderByRaw('CASE WHEN barcode = ? THEN 0 WHEN sku = ? THEN 1 ELSE 2 END', [$term, $term])
            ->orderBy('name')
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->map(function (Product $product) use ($branch): array {
                $stock = $product->branchStock->first();
                $price = $product->branchPrices->first();
                return [
                    'id' => (string) $product->getKey(),
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'track_inventory' => (bool) $product->track_inventory,
                    'quantity_milliunits' => (int) ($stock?->quantity_milliunits ?? 0),
                    'quantity' => number_format(((int) ($stock?->quantity_milliunits ?? 0)) / 1000, 3, '.', ''),
                    'price_kobo' => (int) ($price?->price_kobo ?? $product->default_price_kobo),
                    'price' => number_format(((int) ($price?->price_kobo ?? $product->default_price_kobo)) / 100, 2, '.', ''),
                    'branch_id' => (string) $branch->getKey(),
                ];
            });
    }
}
