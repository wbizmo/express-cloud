<?php

declare(strict_types=1);

namespace Tests\Unit\AccountingOperations;

use App\Models\FixedAsset;
use PHPUnit\Framework\TestCase;

final class FixedAssetTest extends TestCase
{
    public function test_monthly_depreciation_is_straight_line(): void
    {
        $asset = new FixedAsset([
            'cost_kobo' => 1200000,
            'salvage_value_kobo' => 0,
            'useful_life_months' => 12,
        ]);

        self::assertSame(
            100000,
            $asset->monthlyDepreciationKobo(),
        );
    }

    public function test_depreciation_never_uses_negative_base(): void
    {
        $asset = new FixedAsset([
            'cost_kobo' => 100000,
            'salvage_value_kobo' => 200000,
            'useful_life_months' => 12,
        ]);

        self::assertSame(
            0,
            $asset->monthlyDepreciationKobo(),
        );
    }
}
