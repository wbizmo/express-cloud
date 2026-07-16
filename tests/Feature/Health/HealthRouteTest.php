<?php

declare(strict_types=1);

namespace Tests\Feature\Health;

use Tests\TestCase;

final class HealthRouteTest extends TestCase
{
    public function test_health_route_returns_structured_result(): void
    {
        $response = $this->getJson('/api/health');

        self::assertContains(
            $response->status(),
            [200, 503],
        );

        $response
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database' => ['ok', 'detail'],
                    'backup_storage' => ['ok', 'detail'],
                    'application_key' => ['ok', 'detail'],
                ],
            ]);
    }
}
