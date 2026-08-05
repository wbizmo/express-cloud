<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Insights;

use App\Models\Account;
use App\Models\BusinessSnapshot;
use App\Services\Insights\BusinessSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class LisaSnapshotController
{
    public function __construct(private BusinessSnapshotService $snapshots) {}

    public function show(Request $request): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();

        return response()->json($this->snapshots->for(
            $actor,
            $request->string('branch_id')->trim()->toString() ?: null,
            $request->boolean('fresh'),
        ));
    }

    public function evidence(Request $request, BusinessSnapshot $snapshot): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();
        $allowed = $actor->is_allowed_all_branches || $actor->branches()->exists();
        abort_unless($allowed, 404);

        return response()->json([
            'snapshot_id' => (string) $snapshot->getKey(),
            'evidence_hash' => $snapshot->evidence_hash,
            'evidence' => $snapshot->evidence()->orderBy('metric_key')->get([
                'metric_key', 'source_table', 'source_query_hash',
                'value_payload', 'observed_at',
            ]),
        ]);
    }
}
