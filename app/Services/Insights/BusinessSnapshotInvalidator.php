<?php

declare(strict_types=1);

namespace App\Services\Insights;

final readonly class BusinessSnapshotInvalidator
{
    public function __construct(private BusinessSnapshotService $snapshots) {}

    public function created(object $model): void
    {
        $this->snapshots->invalidate();
    }

    public function updated(object $model): void
    {
        $this->snapshots->invalidate();
    }

    public function deleted(object $model): void
    {
        $this->snapshots->invalidate();
    }
}
