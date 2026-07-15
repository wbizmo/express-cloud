<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Support\Authorization\PermissionCatalog;
use Tests\TestCase;

final class PaymentMethodRoutesTest extends TestCase
{
    public function test_payment_method_routes_require_authentication(): void
    {
        $this->get('/admin/payment-methods')->assertRedirect('/');
    }

    public function test_payment_method_permissions_exist(): void
    {
        $permissions = PermissionCatalog::all();

        self::assertContains('payment-methods.view', $permissions);
        self::assertContains('payment-methods.manage', $permissions);
    }
}
