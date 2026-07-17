<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Tests\TestCase;

final class AuthenticationRoutesTest extends TestCase
{
    public function test_shared_login_page_is_the_root_route(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Staff member')
            ->assertSee('Access key')
            ->assertDontSee('Email address')
            ->assertDontSee('Password');
    }

    public function test_guest_cannot_open_profile(): void
    {
        $this->get('/staff/profile')
            ->assertRedirect('/');
    }

    public function test_staff_search_requires_at_least_two_characters_without_database_query(): void
    {
        $this->getJson('/login/staff-search?q=a')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
