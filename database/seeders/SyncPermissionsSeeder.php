<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\AccountPermissionMap;
use App\Support\Permissions\ActivityLogPermissionMap;
use App\Support\Permissions\AreaPlanPermissionMap;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use App\Support\Permissions\ContentPermissionMap;
use App\Support\Permissions\DashboardPermissionMap;
use App\Support\Permissions\ExpensePermissionMap;
use App\Support\Permissions\OperationsPermissionMap;
use App\Support\Permissions\OrdersPermissionMap;
use App\Support\Permissions\RefusedReasonPermissionMap;
use App\Support\Permissions\SettingPermissionMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionMaps = [
            AccountPermissionMap::class,
            ActivityLogPermissionMap::class,
            AreaPlanPermissionMap::class,
            CollectionsReturnsSettlementsPermissionMap::class,
            ContentPermissionMap::class,
            DashboardPermissionMap::class,
            ExpensePermissionMap::class,
            OperationsPermissionMap::class,
            OrdersPermissionMap::class,
            RefusedReasonPermissionMap::class,
            SettingPermissionMap::class,
        ];

        $allPermissions = [];
        foreach ($permissionMaps as $map) {
            if (method_exists($map, 'allPermissionDefinitions')) {
                $allPermissions = array_merge($allPermissions, $map::allPermissionDefinitions());
            }
        }

        // 1. Sync Permissions
        foreach ($allPermissions as $permissionData) {
            Permission::query()->updateOrCreate(
                ['name' => $permissionData['name']],
                [
                    'group' => $permissionData['group'] ?? 'general',
                    'label' => $permissionData['label'] ?? $permissionData['name'],
                    'type' => $permissionData['type'] ?? 'action',
                    'guard_name' => 'web',
                ]
            );
        }

        // 2. Define Roles
        $roles = [
            'super-admin' => 'Super admin',
            'admin' => 'admin',
            'shipper' => 'shipper',
            'client' => 'client',
            'follower' => 'follower',
        ];

        foreach ($roles as $name => $label) {
            // Aggressively find role ignoring case
            $existing = Role::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            
            if ($existing) {
               // Force name update (some DBs require temporary rename for case change)
               if ($existing->name !== $name) {
                   $existing->update(['name' => $name . '_temp']);
               }
               $existing->update([
                   'name' => $name,
                   'label' => $label,
                   'guard_name' => 'web'
               ]);
            } else {
                Role::query()->create([
                    'name' => $name,
                    'label' => $label,
                    'guard_name' => 'web',
                ]);
            }
        }
        
        // 3. Define mapping for existing roles to new roles
        $migrationMap = [
            'Shipper' => 'shipper',
            'Client' => 'client',
            'super admin' => 'super-admin',
        ];

        foreach ($migrationMap as $oldName => $newName) {
            $oldRole = Role::where('name', $oldName)->first();
            $newRole = Role::where('name', $newName)->first();

            if ($oldRole && $newRole && $oldName !== $newName) {
                foreach ($oldRole->users as $user) {
                    if (!$user->hasRole($newName)) {
                        $user->assignRole($newName);
                    }
                }
            }
        }
        
        // 4. Delete other roles
        Role::query()->whereNotIn('name', array_keys($roles))->delete();

        // 3. Assign Permissions to Roles (Basic Initial Setup)
        $superAdminRole = Role::findByName('super-admin');
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());

        // Shipper Permissions — status changes, collections, returns only
        $shipperPermissionNames = array_unique(array_merge(
            [
                'order.page',
                'order.view',
                'order.change-status',
                'order.dashboard.page',
                'order.dashboard.view',
                'order.dashboard.chart.status_donut.view',
                'order.dashboard.card.all_order.view',
                'order.dashboard.card.out_for_delivery.view',
                'order.dashboard.card.hold.view',
                'order.dashboard.card.delivered.view',
                'order.dashboard.card.undelivered.view',
                'shipper-collection.page',
                'shipper-collection.view',
                'shipper-collection.create',
                'shipper-return.page',
                'shipper-return.view',
                'shipper-return.create',
            ],
            Permission::query()->where('name', 'like', 'order.column.%')->where('name', 'like', '%.view')->pluck('name')->all(),
            Permission::query()->where('name', 'like', 'shipper-collection.column.%')->where('name', 'like', '%.view')->pluck('name')->all(),
            Permission::query()->where('name', 'like', 'shipper-return.column.%')->where('name', 'like', '%.view')->pluck('name')->all(),
            Permission::query()->where('name', 'like', 'user.profile.%')->pluck('name')->all(),
            Permission::query()->where('name', 'like', 'visit.%')->pluck('name')->all(),
        ));

        $shipperRole = Role::findByName('shipper');
        $shipperRole->syncPermissions(Permission::whereIn('name', $shipperPermissionNames)->get());

        // Client Permissions — create/view orders only (no shipper, collections, or returns)
        $clientPermissionNames = array_unique(array_merge(
            [
                'order.page',
                'order.view',
                'order.create',
                'order.update',
                'order.change-note',
                'order.change-external-code',
                'order.dashboard.page',
                'order.dashboard.view',
                'order.dashboard.chart.status_donut.view',
                'order.dashboard.card.all_order.view',
                'order.dashboard.card.delivered.view',
                'order.dashboard.card.undelivered.view',
                'order.dashboard.card.out_for_delivery.view',
                'order.dashboard.card.hold.view',
            ],
            Permission::query()->where('name', 'like', 'order.column.%')->where('name', 'like', '%.view')->pluck('name')->all(),
            [
                'order.column.external_code.edit',
                'order.column.receiver_name.edit',
                'order.column.phone.edit',
                'order.column.address.edit',
                'order.column.governorate_id.edit',
                'order.column.city_id.edit',
                'order.column.shipper_user_id.edit',
                'order.column.total_amount.edit',
                'order.column.shipping_fee.edit',
                'order.column.allow_open.edit',
                'order.column.order_note.edit',
            ],
            Permission::query()->where('name', 'like', 'user.profile.%')->pluck('name')->all(),
            Permission::query()->where('name', 'like', 'material-request.%')->pluck('name')->all(),
            [
                'client-settlement.page',
                'client-settlement.view',
                'client-settlement.create',
                'client-settlement.column.settlement_date.edit',
            ],
            Permission::query()->where('name', 'like', 'client-settlement.column.%')->where('name', 'like', '%.view')->pluck('name')->all(),
        ));

        $clientRole = Role::findByName('client');
        $clientRole->syncPermissions(Permission::whereIn('name', $clientPermissionNames)->get());

        // Follower Permissions (Limited View)
        $followerPermissions = Permission::where('name', 'like', '%.view')
            ->orWhere('type', 'page')
            ->orWhere('type', 'column')
            ->get();
        
        $followerRole = Role::findByName('follower');
        $followerRole->syncPermissions($followerPermissions);
        
        $this->command->info('Permissions and Roles synchronized successfully!');
    }
}
