<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Enums\Operations\OperationStatus;
use App\Exceptions\Operations\IdempotencyConflict;
use App\Models\Account;
use App\Models\OperationRequest;
use App\Models\OutboxEvent;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

final readonly class CommandBoundary
{
    public function __construct(
        private RequestFingerprint $fingerprints,
        private TransactionRetrier $transactions,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  Closure(OperationRequest): Model  $callback
     */
    public function execute(
        string $scope,
        string $idempotencyKey,
        array $payload,
        ?Account $actor,
        ?string $branchId,
        Closure $callback,
    ): Model {
        $scope = trim($scope);
        $idempotencyKey = trim($idempotencyKey);

        if ($scope === '' || strlen($scope) > 80) {
            throw new \InvalidArgumentException('A valid operation scope is required.');
        }

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            throw new \InvalidArgumentException(
                'An idempotency key between 1 and 120 characters is required.',
            );
        }

        $fingerprint = $this->fingerprints->hash($payload);
        $operation = $this->reserve(
            $scope,
            $idempotencyKey,
            $fingerprint,
            $actor,
            $branchId,
        );

        $this->assertFingerprint($operation, $fingerprint);

        try {
            return $this->transactions->run(function () use (
                $operation,
                $fingerprint,
                $callback,
            ): Model {
                /** @var OperationRequest $locked */
                $locked = OperationRequest::query()
                    ->whereKey($operation->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertFingerprint($locked, $fingerprint);

                if ($locked->status === OperationStatus::Succeeded) {
                    return $this->resolveResult($locked);
                }

                $locked->forceFill([
                    'status' => OperationStatus::Processing,
                    'attempts' => $locked->attempts + 1,
                    'failure_code' => null,
                    'failure_message' => null,
                    'failed_at' => null,
                    'started_at' => $locked->started_at ?? now(),
                    'lease_expires_at' => now()->addSeconds(
                        max(10, (int) config('operations.lease_seconds', 120)),
                    ),
                ])->save();

                $result = $callback($locked);

                if (! $result->exists || $result->getKey() === null) {
                    throw new \LogicException(
                        'An idempotent command must return a persisted Eloquent model.',
                    );
                }

                OutboxEvent::query()->firstOrCreate(
                    [
                        'operation_request_id' => $locked->getKey(),
                        'event_type' => $locked->scope.'.completed',
                        'aggregate_type' => $result::class,
                        'aggregate_id' => (string) $result->getKey(),
                    ],
                    [
                        'payload' => [
                            'operation_id' => (string) $locked->getKey(),
                            'scope' => $locked->scope,
                            'result_type' => $result::class,
                            'result_id' => (string) $result->getKey(),
                        ],
                        'occurred_at' => now(),
                        'publish_attempts' => 0,
                    ],
                );

                $locked->forceFill([
                    'status' => OperationStatus::Succeeded,
                    'result_type' => $result::class,
                    'result_id' => (string) $result->getKey(),
                    'response_payload' => [
                        'id' => (string) $result->getKey(),
                        'type' => $result::class,
                    ],
                    'completed_at' => now(),
                    'lease_expires_at' => null,
                ])->save();

                return $result;
            });
        } catch (Throwable $exception) {
            $this->recordFailure($operation, $exception);

            throw $exception;
        }
    }

    private function reserve(
        string $scope,
        string $idempotencyKey,
        string $fingerprint,
        ?Account $actor,
        ?string $branchId,
    ): OperationRequest {
        return $this->transactions->run(function () use (
            $scope,
            $idempotencyKey,
            $fingerprint,
            $actor,
            $branchId,
        ): OperationRequest {
            $now = now();

            OperationRequest::query()->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'scope' => $scope,
                'idempotency_key' => $idempotencyKey,
                'request_fingerprint' => $fingerprint,
                'account_id' => $actor?->getKey(),
                'branch_id' => $branchId,
                'status' => OperationStatus::Pending->value,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            /** @var OperationRequest $operation */
            $operation = OperationRequest::query()
                ->where('scope', $scope)
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            return $operation;
        });
    }

    private function assertFingerprint(
        OperationRequest $operation,
        string $fingerprint,
    ): void {
        if (! hash_equals($operation->request_fingerprint, $fingerprint)) {
            throw IdempotencyConflict::fingerprintMismatch($operation->scope);
        }
    }

    private function resolveResult(OperationRequest $operation): Model
    {
        $type = $operation->result_type;
        $id = $operation->result_id;

        if (
            ! is_string($type)
            || ! is_subclass_of($type, Model::class)
            || ! is_string($id)
            || $id === ''
        ) {
            throw new \LogicException(
                'A completed operation does not have a resolvable result.',
            );
        }

        /** @var class-string<Model> $type */
        return $type::query()->findOrFail($id);
    }

    private function recordFailure(
        OperationRequest $operation,
        Throwable $exception,
    ): void {
        try {
            $this->transactions->run(function () use (
                $operation,
                $exception,
            ): void {
                /** @var OperationRequest|null $locked */
                $locked = OperationRequest::query()
                    ->whereKey($operation->getKey())
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $locked instanceof OperationRequest
                    || $locked->status === OperationStatus::Succeeded
                ) {
                    return;
                }

                $locked->forceFill([
                    'status' => OperationStatus::Failed,
                    'failure_code' => Str::limit(
                        $exception::class,
                        190,
                        '',
                    ),
                    'failure_message' => Str::limit(
                        $exception->getMessage(),
                        1000,
                        '',
                    ),
                    'failed_at' => now(),
                    'lease_expires_at' => null,
                ])->save();
            });
        } catch (Throwable) {
            // Preserve the original domain exception. A later retry can still
            // resolve the durable pending/processing operation safely.
        }
    }
}
