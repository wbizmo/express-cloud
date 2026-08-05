<?php

declare(strict_types=1);

namespace App\Services\Performance;

use App\Models\ReferenceCacheVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class ReferenceDataCache
{
    /** @param callable(): mixed $loader */
    public function remember(string $namespace, string $scope, callable $loader): mixed
    {
        $version = (int) ReferenceCacheVersion::query()
            ->whereKey($namespace)
            ->value('version');
        $key = 'reference:'.$namespace.':'.$version.':'.hash('sha256', $scope);

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $value = $loader();
        Cache::put(
            $key,
            $value,
            now()->addSeconds((int) config('performance.reference_cache_seconds', 300)),
        );

        return $value;
    }

    public function invalidate(string $namespace): void
    {
        DB::transaction(function () use ($namespace): void {
            /** @var ReferenceCacheVersion|null $version */
            $version = ReferenceCacheVersion::query()
                ->whereKey($namespace)
                ->lockForUpdate()
                ->first();
            if (! $version instanceof ReferenceCacheVersion) {
                ReferenceCacheVersion::query()->create([
                    'namespace' => $namespace,
                    'version' => 2,
                    'invalidated_at' => now(),
                ]);

                return;
            }
            $version->forceFill([
                'version' => $version->version + 1,
                'invalidated_at' => now(),
            ])->save();
        }, 3);
    }
}
