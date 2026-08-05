<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Services\Performance\ReferenceDataCache;
use Illuminate\Database\Eloquent\Model;

final readonly class ReferenceDataObserver
{
    public function __construct(private ReferenceDataCache $cache) {}

    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $namespace = match (true) {
            $model instanceof Branch => 'branches',
            $model instanceof PaymentMethod => 'payment-methods',
            $model instanceof ProductCategory => 'product-categories',
            default => null,
        };
        if ($namespace !== null) {
            $this->cache->invalidate($namespace);
        }
    }
}
