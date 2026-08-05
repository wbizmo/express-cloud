<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\EnforceBranchScope;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Organisation\AuthorizationService;
use App\Services\Organisation\BranchAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

final class PhaseOneSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function permission_gates_use_the_database_authorization_service(): void
    {
        $account = $this->account();
        $role = Role::query()->create([
            'name' => 'Sales manager',
            'slug' => 'sales-manager-test',
            'is_system' => false,
            'is_active' => true,
        ]);
        $permission = Permission::query()->create([
            'name' => 'View all sales',
            'slug' => 'sales.view.all',
            'group' => 'Commercial',
        ]);
        $role->permissions()->attach($permission);
        $account->roles()->attach($role);

        app(AuthorizationService::class)->forget($account);

        self::assertTrue(Gate::forUser($account)->allows('sales.view.all'));
        self::assertFalse(Gate::forUser($account)->allows('sales.void'));
    }

    #[Test]
    public function bound_resources_and_submitted_branch_ids_are_concealed_cross_branch(): void
    {
        $actor = $this->account();
        $allowed = $this->branch('ALLOWED');
        $denied = $this->branch('DENIED');
        $actor->branches()->attach($allowed);
        app(BranchAccess::class)->forget($actor);

        $middleware = app(EnforceBranchScope::class);

        $boundRequest = $this->requestWithRoute($actor, ['branch' => $denied]);
        $this->expectException(NotFoundHttpException::class);
        $middleware->handle($boundRequest, static fn (): Response => response('ok'));
    }

    #[Test]
    public function assigned_branch_passes_the_central_scope_boundary(): void
    {
        $actor = $this->account();
        $branch = $this->branch('ASSIGNED');
        $actor->branches()->attach($branch);
        app(BranchAccess::class)->forget($actor);

        $request = $this->requestWithRoute($actor, ['branch' => $branch]);
        $response = app(EnforceBranchScope::class)->handle(
            $request,
            static fn (): Response => response('ok'),
        );

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function web_responses_receive_security_headers(): void
    {
        $request = Request::create('/', 'GET');
        $response = app(ApplySecurityHeaders::class)->handle(
            $request,
            static fn (): Response => response('<html></html>'),
        );

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertStringContainsString("frame-ancestors 'none'", (string) $response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function desktop_and_mobile_navigation_use_one_visibility_service(): void
    {
        $desktop = file_get_contents(resource_path('views/components/navigation/sidebar.blade.php'));
        $mobile = file_get_contents(resource_path('views/components/navigation/mobile-drawer.blade.php'));

        self::assertIsString($desktop);
        self::assertIsString($mobile);
        self::assertStringContainsString('NavigationVisibility::class', $desktop);
        self::assertStringContainsString('NavigationVisibility::class', $mobile);
        self::assertStringNotContainsString("@foreach (config('navigation.primary')", $mobile);
    }

    private function account(): Account
    {
        return Account::query()->create([
            'public_id' => Str::uuid()->toString(),
            'first_name' => 'Phase',
            'last_name' => 'One',
            'login_key_encrypted' => 'test-ciphertext',
            'login_key_blind_index' => hash('sha256', Str::uuid()->toString()),
            'login_key_version' => 1,
            'status' => 'active',
            'is_allowed_all_branches' => false,
        ]);
    }

    private function branch(string $code): Branch
    {
        return Branch::query()->create([
            'name' => $code.' Branch',
            'code' => $code,
            'address' => 'Test address',
            'status' => 'active',
            'is_head_office' => false,
        ]);
    }

    /** @param array<string, mixed> $parameters */
    private function requestWithRoute(Account $actor, array $parameters): Request
    {
        $request = Request::create('/phase-one-test', 'POST');
        $request->setUserResolver(static fn (): Account => $actor);
        $route = new Route(['POST'], '/phase-one-test', static fn (): null => null);
        $route->bind($request);

        foreach ($parameters as $key => $value) {
            $route->setParameter($key, $value);
        }

        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }
}
