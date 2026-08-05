<?php

declare(strict_types=1);

namespace App\Services\Resilience;

use App\Enums\Operations\OperationStatus;
use App\Models\Account;
use App\Models\OperationRequest;
use App\Services\Organisation\AuthorizationService;
use App\Services\Organisation\BranchAccess;
use Carbon\CarbonImmutable;

final readonly class OperationRecoveryService
{
    public function __construct(
        private AuthorizationService $authorization,
        private BranchAccess $branches,
    ) {}

    /** @return array<string, mixed> */
    public function status(Account $actor, string $scope, string $idempotencyKey): array
    {
        /** @var OperationRequest $operation */
        $operation = OperationRequest::query()
            ->where('scope', trim($scope))
            ->where('idempotency_key', trim($idempotencyKey))
            ->firstOrFail();

        $ownsOperation = (string) $operation->account_id === (string) $actor->getKey();
        abort_unless($ownsOperation || $this->authorization->isSystemOwner($actor), 404);

        if (is_string($operation->branch_id) && $operation->branch_id !== '') {
            $this->branches->enforce($actor, $operation->branch_id);
        }

        $status = $operation->status instanceof OperationStatus
            ? $operation->status->value
            : (string) $operation->status;

        return [
            'operation_id' => (string) $operation->getKey(),
            'scope' => $operation->scope,
            'idempotency_key' => $operation->idempotency_key,
            'status' => $status,
            'attempts' => $operation->attempts,
            'result' => $operation->result_id === null ? null : [
                'type' => $operation->result_type,
                'id' => $operation->result_id,
            ],
            'failure' => $operation->failure_code === null ? null : [
                'code' => $operation->failure_code,
                'message' => $operation->failure_message,
            ],
            'completed_at' => $operation->completed_at === null ? null : CarbonImmutable::parse($operation->completed_at)->toAtomString(),
            'failed_at' => $operation->failed_at === null ? null : CarbonImmutable::parse($operation->failed_at)->toAtomString(),
        ];
    }
}
