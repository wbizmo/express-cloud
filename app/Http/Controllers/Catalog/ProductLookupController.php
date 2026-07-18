<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Models\Account;
use App\Models\Branch;
use App\Services\Catalog\ProductLookup;
use App\Services\Organisation\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ProductLookupController
{
    public function __construct(
        private ProductLookup $lookup,
        private AuthorizationService $authorization,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $context = $request->string('context')->toString();
        $permission = match ($context) {
            'sale' => 'catalog.sale-search',
            'inventory' => 'catalog.inventory-search',
            'procurement' => 'catalog.procurement-search',
            default => null,
        };
        abort_unless($permission !== null && $this->authorization->hasPermission($actor, $permission), 404);

        $branch = Branch::query()->findOrFail($request->string('branch_id')->toString());

        return response()->json([
            'data' => $this->lookup->search($actor, $branch, $request->string('q')->toString())->all(),
        ]);
    }
}
