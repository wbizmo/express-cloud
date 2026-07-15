<?php

declare(strict_types=1);

namespace Tests\Unit\Documents;

use App\Models\Sale;
use App\Services\Documents\SaleVerificationToken;
use Tests\TestCase;

final class SaleVerificationTokenTest extends TestCase
{
    public function test_token_is_deterministic_and_tamper_sensitive(): void
    {
        config()->set('app.key', 'base64:test-secret');

        $sale = new Sale([
            'sale_code' => 'INV-TEST',
        ]);

        $sale->setAttribute(
            'id',
            '01TESTSALEULID000000000000',
        );

        $service = new SaleVerificationToken;
        $token = $service->issue($sale);

        self::assertTrue(
            $service->valid($sale, $token),
        );

        self::assertFalse(
            $service->valid($sale, $token.'x'),
        );
    }
}
