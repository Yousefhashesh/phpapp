<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permission = DB::table('permissions')
            ->where('name', 'setting.financial-formulas.update')
            ->where('guard_name', 'web')
            ->first();

        if ($permission === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'setting.financial-formulas.update',
                'guard_name' => 'web',
                'group' => 'setting',
                'label' => 'Update financial formulas',
                'type' => 'button',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permissionId = $permission->id;
            DB::table('permissions')
                ->where('id', $permissionId)
                ->update([
                    'group' => 'setting',
                    'label' => 'Update financial formulas',
                    'type' => 'button',
                    'updated_at' => now(),
                ]);
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['super-admin', 'admin', 'setting-manager'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', 'setting.financial-formulas.update')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
