<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AccountingOperations;

use App\Actions\AccountingOperations\CreateFixedAsset;
use App\Http\Requests\AccountingOperations\StoreFixedAssetRequest;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FixedAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class FixedAssetController
{
    public function __construct(
        private CreateFixedAsset $create,
    ) {}

    public function index(): View
    {
        return view('admin.accounting-operations.assets', [
            'assets' => FixedAsset::query()
                ->orderBy('name')
                ->paginate(40),
            'branches' => Branch::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StoreFixedAssetRequest $request,
    ): RedirectResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $asset = $this->create->execute(
            $request,
            $actor,
            $request->validated(),
        );

        return back()->with(
            'status',
            "Asset {$asset->asset_code} created.",
        );
    }
}
