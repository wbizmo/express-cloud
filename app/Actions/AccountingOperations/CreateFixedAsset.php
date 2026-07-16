<?php

declare(strict_types=1);

namespace App\Actions\AccountingOperations;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\Catalog\MoneyInput;
use App\Services\Organisation\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateFixedAsset
{
    public function __construct(
        private MoneyInput $money,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        Request $request,
        Account $actor,
        array $data,
    ): FixedAsset {
        return DB::transaction(function () use (
            $request,
            $actor,
            $data,
        ): FixedAsset {
            $asset = FixedAsset::query()->create([
                'asset_code' => 'AST-'.now()->format('ymd').'-'
                    .Str::upper(Str::random(6)),
                'name' => (string) $data['name'],
                'category' => (string) $data['category'],
                'branch_id' => $data['branch_id'] ?? null,
                'custodian_account_id' => $data[
                    'custodian_account_id'
                ] ?? null,
                'acquired_at' => (string) $data['acquired_at'],
                'cost_kobo' => $this->money->toKobo(
                    $data['cost'],
                ) ?? 0,
                'salvage_value_kobo' => $this->money->toKobo(
                    $data['salvage_value'] ?? null,
                ) ?? 0,
                'useful_life_months' => (int) $data[
                    'useful_life_months'
                ],
                'serial_number' => $data['serial_number'] ?? null,
                'location' => $data['location'] ?? null,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'created_by_account_id' => $actor->getKey(),
            ]);

            $this->audit->record(
                $request,
                'fixed-asset.created',
                'fixed_asset',
                $asset,
                after: [
                    'asset_code' => $asset->asset_code,
                    'cost_kobo' => $asset->cost_kobo,
                    'useful_life_months' => $asset->useful_life_months,
                ],
            );

            return $asset;
        }, 3);
    }
}
