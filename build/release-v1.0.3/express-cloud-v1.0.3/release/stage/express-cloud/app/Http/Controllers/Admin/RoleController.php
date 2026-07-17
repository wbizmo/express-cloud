<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Organisation\AuditLogger;
use App\Support\Authorization\PermissionCatalog;
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

    public function store(
        StoreRoleRequest $request,
    ): RedirectResponse {
        $role = DB::transaction(function () use ($request): Role {
            $role = Role::query()->create([
                ...$request->safe()->only([
                    'name',
                    'slug',
                    'description',
                ]),
                'is_system' => false,
                'is_active' => true,
            ]);

            $permissionIds = Permission::query()
                ->whereIn(
                    'slug',
                    $request->array('permissions'),
                )
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
                'permissions' => $request->array('permissions'),
            ],
        );

        return back()->with('status', 'Role created.');
    }
}
