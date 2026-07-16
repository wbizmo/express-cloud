<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

final class ApiRoutesTest extends TestCase
{
    public function test_openapi_document_is_public(): void
    {
        $this->getJson('/api/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0');
    }

    public function test_versioned_api_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/products')
            ->assertUnauthorized()
            ->assertJsonPath(
                'error_code',
                'API_TOKEN_REQUIRED',
            );
    }

    public function test_admin_token_screen_requires_authentication(): void
    {
        $this->get('/admin/api/tokens')->assertRedirect('/');
    }
}
