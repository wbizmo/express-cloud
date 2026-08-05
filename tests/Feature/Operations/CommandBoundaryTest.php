<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Exceptions\Operations\IdempotencyConflict;
use App\Models\Branch;
use App\Models\OperationRequest;
use App\Models\OutboxEvent;
use App\Services\Operations\CommandBoundary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CommandBoundaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_duplicate_retry_returns_the_original_result_without_reexecuting(): void
    {
        $executions = 0;
        $commands = app(CommandBoundary::class);
        $callback = function () use (&$executions): Branch {
            $executions++;

            return Branch::query()->create([
                'name' => 'Idempotent Branch',
                'code' => 'IDEMPOTENT',
                'address' => 'Test address',
                'status' => 'active',
                'is_head_office' => false,
            ]);
        };
        $first = $commands->execute(
            'test.branch.create',
            'same-key',
            ['name' => 'Idempotent Branch'],
            null,
            null,
            $callback,
        );
        $second = $commands->execute(
            'test.branch.create',
            'same-key',
            ['name' => 'Idempotent Branch'],
            null,
            null,
            $callback,
        );

        self::assertSame((string) $first->getKey(), (string) $second->getKey());
        self::assertSame(1, $executions);
        self::assertSame(1, Branch::query()->where('code', 'IDEMPOTENT')->count());
        self::assertSame(1, OperationRequest::query()->count());
        self::assertSame(1, OutboxEvent::query()->count());
    }

    #[Test]
    public function a_reused_key_with_a_different_payload_is_rejected(): void
    {
        $commands = app(CommandBoundary::class);
        $commands->execute(
            'test.branch.create',
            'conflict-key',
            ['name' => 'Original'],
            null,
            null,
            static fn (): Branch => Branch::query()->create([
                'name' => 'Original',
                'code' => 'ORIGINAL',
                'address' => 'Test address',
                'status' => 'active',
                'is_head_office' => false,
            ]),
        );

        $this->expectException(IdempotencyConflict::class);
        $commands->execute(
            'test.branch.create',
            'conflict-key',
            ['name' => 'Changed'],
            null,
            null,
            static fn (): Branch => throw new \LogicException(
                'The conflicting callback must not execute.',
            ),
        );
    }
}
