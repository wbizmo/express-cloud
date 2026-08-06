<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UiRuntimeRecoveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function application_layout_provides_livewire_and_immediate_cloak_protection(): void
    {
        $layout = file_get_contents(
            resource_path('views/components/layout/app.blade.php'),
        );

        self::assertIsString($layout);
        self::assertStringContainsString('@livewireScripts', $layout);
        self::assertStringContainsString(
            '[x-cloak] { display: none !important; }',
            $layout,
        );
        self::assertStringContainsString(
            "'unsafe-eval'",
            (string) config('security.content_security_policy'),
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('Staff member')
            ->assertSee('Access key')
            ->assertSee('livewire', false);
    }

    #[Test]
    public function active_staff_search_supports_sqlite_first_and_full_names(): void
    {
        Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Demo',
            'last_name' => 'Owner',
            'login_key_encrypted' => 'fixture-ciphertext',
            'login_key_blind_index' => hash(
                'sha256',
                Str::uuid()->toString(),
            ),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => true,
        ]);

        $this->getJson('/login/staff-search?q=dem')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Demo Owner');

        $this->getJson('/login/staff-search?q=demo%20owner')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Demo Owner');

        $this->getJson('/login/staff-search?q=owner%20demo')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Demo Owner');
    }
}
