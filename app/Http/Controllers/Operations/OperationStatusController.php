<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Enums\Operations\OperationStatus;
use App\Models\Account;
use App\Models\OperationRequest;
use App\Services\Organisation\AuthorizationService;
use App\Services\Organisation\BranchAccess;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class OperationStatusController
{
    public function __construct(
        private AuthorizationService $authorization,
        private BranchAccess $branches,
    ) {}

    public function show(
        Request $request,
        OperationRequest $operation,
    ): JsonResponse {
        /** @var Account $actor */
        $actor = $request->user();

        $ownsOperation = (string) $operation->account_id
            === (string) $actor->getKey();
        $isSystemOwner = $this->authorization->isSystemOwner($actor);

        abort_unless($ownsOperation || $isSystemOwner, 404);

        if (is_string($operation->branch_id) && $operation->branch_id !== '') {
            $this->branches->enforce($actor, $operation->branch_id);
        }

        $status = $operation->getAttribute('status');
        $statusValue = $status instanceof OperationStatus
            ? $status->value
            : (string) $status;
        $completedAt = $operation->getAttribute('completed_at');
        $failedAt = $operation->getAttribute('failed_at');

        return response()->json([
            'id' => (string) $operation->getKey(),
            'scope' => $operation->scope,
            'status' => $statusValue,
            'attempts' => $operation->attempts,
            'result' => $operation->result_id === null ? null : [
                'type' => $operation->result_type,
                'id' => $operation->result_id,
            ],
            'failure' => $statusValue !== OperationStatus::Failed->value ? null : [
                'code' => $operation->failure_code,
                'message' => $operation->failure_message,
            ],
            'completed_at' => $completedAt instanceof DateTimeInterface
                ? $completedAt->format(DATE_ATOM)
                : null,
            'failed_at' => $failedAt instanceof DateTimeInterface
                ? $failedAt->format(DATE_ATOM)
                : null,
        ]);
    }
}
