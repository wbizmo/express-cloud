<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class DesignSystemTest extends TestCase
{
    public function test_root_route_remains_the_shared_login_entry(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Express Cloud')
            ->assertSee('Sign in')
            ->assertSee('Staff member')
            ->assertSee('Access key');
    }

    public function test_local_ui_preview_renders_the_responsive_shell(): void
    {
        $this->get('/ui-preview')
            ->assertOk()
            ->assertSee('Operations overview')
            ->assertSee('Lisa AI')
            ->assertSee('Log out')
            ->assertSee('My profile');
    }

    public function test_custom_error_views_render_without_internal_details(): void
    {
        $html = view('errors.500')->render();

        self::assertStringContainsString('Oops, something went wrong', $html);
        self::assertStringNotContainsString('Stack trace', $html);
        self::assertStringNotContainsString('SQLSTATE', $html);
    }

    public function test_ui_configuration_matches_the_locked_shell_dimensions(): void
    {
        self::assertSame(280, config('ui.sidebar.expanded_width'));
        self::assertSame(72, config('ui.sidebar.collapsed_width'));
        self::assertSame(64, config('ui.topbar_height'));
    }
}
