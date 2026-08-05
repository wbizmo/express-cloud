<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Services\Operations\DailyCloseWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SignedDailyCloseController
{
    public function __construct(private DailyCloseWorkflow $workflow) {}

    public function __invoke(Request $request): JsonResponse
    {
        $date = $request->string('business_date')->trim()->toString();
        $date = $date !== '' ? CarbonImmutable::parse($date)->toDateString() : now()->subDay()->toDateString();
        $run = $this->workflow->run($date);

        return response()->json([
            'id' => (string) $run->getKey(),
            'business_date' => (string) $run->business_date,
            'status' => $run->status,
            'attempt_count' => $run->attempt_count,
            'completed_at' => $run->completed_at === null ? null : CarbonImmutable::parse($run->completed_at)->toAtomString(),
        ]);
    }
}
