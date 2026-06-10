<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $clientRoleId = DB::table('roles')->where('name', 'client')->value('id');
        if (! $clientRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', [
                'client-settlement.page',
                'client-settlement.view',
                'client-settlement.create',
                'client-settlement.column.settlement_date.edit',
            ])
            ->orWhere(function ($query): void {
                $query->where('name', 'like', 'client-settlement.column.%')
                    ->where('name', 'like', '%.view');
            })
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => $clientRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
