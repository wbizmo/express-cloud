<?php

declare(strict_types=1);

namespace App\Services\Release;

use App\Models\DataBackfillRun;
use App\Models\FinancialPosting;
use App\Models\OperationRequest;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DataBackfillService
{
    public function run(string $sourceLabel, int $chunkSize = 500): DataBackfillRun
    {
        /** @var DataBackfillRun $run */
        $run = DataBackfillRun::query()->create([
            'source_label' => $sourceLabel,
            'status' => 'running',
            'checkpoint' => [],
            'counts' => [],
            'started_at' => now(),
        ]);
        $counts = ['sales_classified' => 0, 'movements_linked' => 0, 'operations_checked' => 0];
        try {
            Sale::query()->orderBy('id')->chunkById($chunkSize, function ($sales) use (&$counts, $run): void {
                foreach ($sales as $sale) {
                    $hasPosting = FinancialPosting::query()->where('source_type', $sale::class)->where('source_id', $sale->getKey())->exists();
                    if ($hasPosting) {
                        $counts['sales_classified']++;
                    }
                }
                $run->forceFill(['checkpoint' => ['sales_last_id' => (string) optional($sales->last())->getKey()]])->save();
            });
            StockMovement::query()->orderBy('id')->chunkById($chunkSize, function ($movements) use (&$counts): void {
                foreach ($movements as $movement) {
                    if ($movement->operation_request_id !== null) {
                        $counts['movements_linked']++;
                    }
                }
            });
            $counts['operations_checked'] = (int) OperationRequest::query()->count();
            DB::transaction(function () use ($run, $counts): void {
                $run->forceFill(['status' => 'completed', 'counts' => $counts, 'completed_at' => now()])->save();
            });
        } catch (Throwable $exception) {
            $run->forceFill(['status' => 'failed', 'counts' => $counts, 'failure_message' => $exception->getMessage(), 'completed_at' => now()])->save();
            throw $exception;
        }

        return $run;
    }
}
