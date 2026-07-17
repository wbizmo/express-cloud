<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Organisation\AuditLogger;
use App\Support\Authorization\PermissionCatalog;
use App\Support\Authorization\RolePermissionPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

final readonly class RoleController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()
                ->withCount(['accounts', 'permissions'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->paginate(25),
            'permissionGroups' => PermissionCatalog::grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $slug = $request->string('slug')->toString();
        $permissions = RolePermissionPolicy::constrain(
            $name,
            $slug,
            $request->array('permissions'),
        );

        $role = DB::transaction(function () use ($request, $name, $slug, $permissions): Role {
            $role = Role::query()->create([
                'name' => $name,
                'slug' => $slug,
                'description' => $request->string('description')->toString() ?: null,
                'is_system' => false,
                'is_active' => true,
            ]);

            $permissionIds = Permission::query()
                ->whereIn('slug', $permissions)
                ->pluck('id');

            $role->permissions()->sync($permissionIds);

            return $role;
        });

        $this->audit->record(
            $request,
            'role.created',
            'role',
            $role,
            after: [
                'name' => $role->name,
                'slug' => $role->slug,
                'permissions' => $permissions,
            ],
        );

        return back()->with('status', 'Role created.');
    }
}
