<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\BlindIndex;
use PHPUnit\Framework\TestCase;

final class BlindIndexTest extends TestCase
{
    public function test_lookup_index_is_deterministic_and_normalized(): void
    {
        $service = new BlindIndex('test-blind-index-key');

        self::assertSame(
            $service->make(' Staff-Key-001 '),
            $service->make('staff-key-001'),
        );
    }

    public function test_different_values_have_different_indexes(): void
    {
        $service = new BlindIndex('test-blind-index-key');

        self::assertNotSame(
            $service->make('staff-key-001'),
            $service->make('staff-key-002'),
        );
    }
}
