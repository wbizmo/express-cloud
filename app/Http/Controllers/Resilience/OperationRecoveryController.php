<?php

declare(strict_types=1);

namespace App\Http\Controllers\Resilience;

use App\Models\Account;
use App\Services\Resilience\OperationRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class OperationRecoveryController
{
    public function __construct(private OperationRecoveryService $recovery) {}

    public function __invoke(Request $request, string $scope, string $idempotencyKey): JsonResponse
    {
        /** @var Account $actor */
        $actor = $request->user();

        return response()->json($this->recovery->status($actor, $scope, $idempotencyKey));
    }
}
