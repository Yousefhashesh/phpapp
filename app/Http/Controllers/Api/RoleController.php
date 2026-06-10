<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class RoleController
{
    private function authorizePermission(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated');
        abort_unless($user->can($permission), 403, 'Unauthorized: missing permission ' . $permission);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'role.view');

        $roles = Role::query()
            ->with(['permissions:id,name,group,label,type'])
            ->with(['users' => function($query) {
                $query->select('users.id', 'users.name', 'users.avatar')->limit(5);
            }])
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'label', 'is_active', 'created_at', 'updated_at']);

        return response()->json([
            'data' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'role.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required'],
        ]);

        $permissionNames = $this->resolvePermissionNames($data['permissions'] ?? []);

        $role = DB::transaction(function () use ($data, $permissionNames): Role {
            $roleName = $data['name'];
            $roleLabel = ucfirst($roleName);

            $role = Role::query()->create([
                'name' => $roleName,
                'guard_name' => 'web',
                'label' => $data['label'] ?? $roleLabel,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $role->syncPermissions($permissionNames);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role;
        });

        $role->load(['permissions:id,name,group,label,type'])->loadCount(['permissions', 'users']);

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $role,
        ], 201);
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'role.view');

        $role->load(['permissions:id,name,group,label,type'])->loadCount(['permissions', 'users']);

        return response()->json([
            'data' => $role,
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'role.update');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'label' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required'],
        ]);

        $permissionNames = array_key_exists('permissions', $data)
            ? $this->resolvePermissionNames($data['permissions'] ?? [])
            : null;

        DB::transaction(function () use ($data, $role, $permissionNames): void {
            $roleName = $data['name'] ?? $role->name;
            $roleLabel = ucfirst($roleName);

            $role->update([
                'name' => $roleName,
                'label' => array_key_exists('label', $data) ? $data['label'] : $roleLabel,
                'is_active' => $data['is_active'] ?? $role->is_active,
            ]);

            if ($permissionNames !== null) {
                $role->syncPermissions($permissionNames);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        $role->load(['permissions:id,name,group,label,type'])->loadCount(['permissions', 'users']);

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => $role,
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission($request, 'role.delete');

        $role->loadCount('users');

        if ($role->users_count > 0) {
            abort(422, 'Cannot delete a role assigned to users.');
        }

        DB::transaction(function () use ($role): void {
            $role->syncPermissions([]);
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * @param  array<int, mixed>  $permissions
     * @return array<int, string>
     */
    private function resolvePermissionNames(array $permissions): array
    {
        if ($permissions === []) {
            return [];
        }

        $ids = [];
        $names = [];

        foreach ($permissions as $permission) {
            if (is_int($permission) || ctype_digit((string) $permission)) {
                $ids[] = (int) $permission;

                continue;
            }

            $names[] = (string) $permission;
        }

        $resolvedNames = Permission::query()
            ->where(function ($query) use ($ids, $names): void {
                if ($ids !== []) {
                    $query->whereIn('id', $ids);
                }

                if ($names !== []) {
                    if ($ids !== []) {
                        $query->orWhereIn('name', $names);
                    } else {
                        $query->whereIn('name', $names);
                    }
                }
            })
            ->pluck('name')
            ->unique()
            ->values()
            ->all();

        if (count($resolvedNames) !== count(array_unique(array_map('strval', $permissions)))) {
            abort(422, 'One or more permissions are invalid.');
        }

        return $resolvedNames;
    }
}
