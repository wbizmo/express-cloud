<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Services\Operations\RequestFingerprint;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RequestFingerprintTest extends TestCase
{
    #[Test]
    public function associative_key_order_and_transport_fields_do_not_change_the_hash(): void
    {
        $fingerprints = new RequestFingerprint;
        $left = $fingerprints->hash([
            'branch_id' => '01TEST',
            'items' => [['quantity' => '2.000', 'product_id' => '01PRODUCT']],
            '_token' => 'first',
            'idempotency_key' => 'key-a',
        ]);
        $right = $fingerprints->hash([
            'idempotency_key' => 'key-b',
            'items' => [['product_id' => '01PRODUCT', 'quantity' => '2.000']],
            'branch_id' => '01TEST',
            '_token' => 'second',
        ]);

        self::assertSame($left, $right);
    }

    #[Test]
    public function list_order_remains_significant(): void
    {
        $fingerprints = new RequestFingerprint;

        self::assertNotSame(
            $fingerprints->hash(['items' => ['a', 'b']]),
            $fingerprints->hash(['items' => ['b', 'a']]),
        );
    }
}
