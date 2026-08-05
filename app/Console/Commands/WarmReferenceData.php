<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Services\Performance\ReferenceDataCache;
use Illuminate\Console\Command;

final class WarmReferenceData extends Command
{
    protected $signature = 'performance:warm-reference-data';

    protected $description = 'Warm versioned low-volatility reference data caches.';

    public function handle(ReferenceDataCache $cache): int
    {
        $cache->remember('branches', 'active', static fn () => Branch::query()
            ->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code'])->all());
        $cache->remember('payment-methods', 'active-pos', static fn () => PaymentMethod::query()
            ->where('is_active', true)->where('is_visible_in_pos', true)
            ->orderBy('name')->get(['id', 'name', 'method_type'])->all());
        $cache->remember('product-categories', 'active', static fn () => ProductCategory::query()
            ->where('status', 'active')->orderBy('name')->get(['id', 'name'])->all());
        $this->info('Reference data cache warmed.');

        return self::SUCCESS;
    }
}
