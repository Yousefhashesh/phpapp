<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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
use Spatie\Permission\PermissionRegistrar;

class ExpenseAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [];

        $definitions = [
            ...ExpensePermissionMap::allPermissionDefinitions(),
            ...AccountPermissionMap::allPermissionDefinitions(),
            ...ContentPermissionMap::allPermissionDefinitions(),
            ...SettingPermissionMap::allPermissionDefinitions(),
            ...AreaPlanPermissionMap::allPermissionDefinitions(),
            ...OperationsPermissionMap::allPermissionDefinitions(),
            ...RefusedReasonPermissionMap::allPermissionDefinitions(),
            ...OrdersPermissionMap::allPermissionDefinitions(),
            ...DashboardPermissionMap::allPermissionDefinitions(),
            ...CollectionsReturnsSettlementsPermissionMap::allPermissionDefinitions(),
            ...ActivityLogPermissionMap::allPermissionDefinitions(),
        ];

        foreach ($definitions as $permissionData) {
            $permission = Permission::query()->updateOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group' => $permissionData['group'],
                    'label' => $permissionData['label'],
                    'type' => $permissionData['type'],
                ]
            );

            $permissionNames[] = $permission->name;
        }

        $allPermissions = Permission::query()->whereIn('name', $permissionNames)->pluck('name')->all();

        $viewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $managerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'expense.') || str_starts_with($name, 'expense-category.')
        ));

        $accountViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'user.')
            || str_starts_with($name, 'client.')
            || str_starts_with($name, 'shipper.')
        ));

        $accountViewerPermissions = array_values(array_filter($accountViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $accountManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'user.')
            || str_starts_with($name, 'client.')
            || str_starts_with($name, 'shipper.')
        ));

        $contentViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'content.')
        ));

        $contentViewerPermissions = array_values(array_filter($contentViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $contentManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'content.')
        ));

        $areaViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'governorate.')
            || str_starts_with($name, 'city.')
        ));

        $areaViewerPermissions = array_values(array_filter($areaViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $areaManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'governorate.')
            || str_starts_with($name, 'city.')
        ));

        $planViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'plan.')
            || str_starts_with($name, 'plan-price.')
        ));

        $planViewerPermissions = array_values(array_filter($planViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $planManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'plan.')
            || str_starts_with($name, 'plan-price.')
        ));

        $operationsViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'material.')
            || str_starts_with($name, 'material-request.')
            || str_starts_with($name, 'material-request-item.')
            || str_starts_with($name, 'pickup-request.')
            || str_starts_with($name, 'visit.')
        ));

        $operationsViewerPermissions = array_values(array_filter($operationsViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $operationsManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'material.')
            || str_starts_with($name, 'material-request.')
            || str_starts_with($name, 'material-request-item.')
            || str_starts_with($name, 'pickup-request.')
            || str_starts_with($name, 'visit.')
        ));

        $refusedReasonViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'refused-reason.')
        ));

        $refusedReasonViewerPermissions = array_values(array_filter($refusedReasonViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $refusedReasonManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'refused-reason.')
        ));

        $orderViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'order.')
        ));

        $orderViewerPermissions = array_values(array_filter($orderViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $orderManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'order.')
        ));

        $collectionViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'shipper-collection.')
            || str_starts_with($name, 'shipper-return.')
        ));

        $collectionViewerPermissions = array_values(array_filter($collectionViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $collectionManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'shipper-collection.')
            || str_starts_with($name, 'shipper-return.')
        ));

        $settlementViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'client-settlement.')
            || str_starts_with($name, 'client-return.')
        ));

        $settlementViewerPermissions = array_values(array_filter($settlementViewerPermissions, static fn (string $name): bool => str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $settlementManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'client-settlement.')
            || str_starts_with($name, 'client-return.')
        ));

        $activityLogPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'activity-log.')
        ));

        $superAdminRole = Role::query()->firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ], [
            'label' => 'Super Admin',
            'is_active' => true,
        ]);
        $superAdminRole->syncPermissions($allPermissions);

        $managerRole = Role::query()->firstOrCreate([
            'name' => 'expense-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Expense Manager',
            'is_active' => true,
        ]);
        $managerRole->syncPermissions($managerPermissions);

        $viewerRole = Role::query()->firstOrCreate([
            'name' => 'expense-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Expense Viewer',
            'is_active' => true,
        ]);
        $viewerRole->syncPermissions($viewerPermissions);

        $accountManagerRole = Role::query()->firstOrCreate([
            'name' => 'account-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Account Manager',
            'is_active' => true,
        ]);
        $accountManagerRole->syncPermissions($accountManagerPermissions);

        $accountViewerRole = Role::query()->firstOrCreate([
            'name' => 'account-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Account Viewer',
            'is_active' => true,
        ]);
        $accountViewerRole->syncPermissions($accountViewerPermissions);

        $contentManagerRole = Role::query()->firstOrCreate([
            'name' => 'content-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Content Manager',
            'is_active' => true,
        ]);
        $contentManagerRole->syncPermissions($contentManagerPermissions);

        $contentViewerRole = Role::query()->firstOrCreate([
            'name' => 'content-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Content Viewer',
            'is_active' => true,
        ]);
        $contentViewerRole->syncPermissions($contentViewerPermissions);

        $settingManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool => str_starts_with($name, 'setting.')
        ));

        $settingManagerRole = Role::query()->firstOrCreate([
            'name' => 'setting-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Setting Manager',
            'is_active' => true,
        ]);
        $settingManagerRole->syncPermissions($settingManagerPermissions);

        $areaManagerRole = Role::query()->firstOrCreate([
            'name' => 'area-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Area Manager',
            'is_active' => true,
        ]);
        $areaManagerRole->syncPermissions($areaManagerPermissions);

        $areaViewerRole = Role::query()->firstOrCreate([
            'name' => 'area-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Area Viewer',
            'is_active' => true,
        ]);
        $areaViewerRole->syncPermissions($areaViewerPermissions);

        $planManagerRole = Role::query()->firstOrCreate([
            'name' => 'plan-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Plan Manager',
            'is_active' => true,
        ]);
        $planManagerRole->syncPermissions($planManagerPermissions);

        $planViewerRole = Role::query()->firstOrCreate([
            'name' => 'plan-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Plan Viewer',
            'is_active' => true,
        ]);
        $planViewerRole->syncPermissions($planViewerPermissions);

        $operationsManagerRole = Role::query()->firstOrCreate([
            'name' => 'operations-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Operations Manager',
            'is_active' => true,
        ]);
        $operationsManagerRole->syncPermissions($operationsManagerPermissions);

        $operationsViewerRole = Role::query()->firstOrCreate([
            'name' => 'operations-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Operations Viewer',
            'is_active' => true,
        ]);
        $operationsViewerRole->syncPermissions($operationsViewerPermissions);

        $refusedReasonManagerRole = Role::query()->firstOrCreate([
            'name' => 'refused-reason-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Refused Reason Manager',
            'is_active' => true,
        ]);
        $refusedReasonManagerRole->syncPermissions($refusedReasonManagerPermissions);

        $refusedReasonViewerRole = Role::query()->firstOrCreate([
            'name' => 'refused-reason-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Refused Reason Viewer',
            'is_active' => true,
        ]);
        $refusedReasonViewerRole->syncPermissions($refusedReasonViewerPermissions);

        $orderManagerRole = Role::query()->firstOrCreate([
            'name' => 'order-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Order Manager',
            'is_active' => true,
        ]);
        $orderManagerRole->syncPermissions($orderManagerPermissions);

        $orderViewerRole = Role::query()->firstOrCreate([
            'name' => 'order-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Order Viewer',
            'is_active' => true,
        ]);
        $orderViewerRole->syncPermissions($orderViewerPermissions);

        $collectionManagerRole = Role::query()->firstOrCreate([
            'name' => 'collection-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Collection Manager',
            'is_active' => true,
        ]);
        $collectionManagerRole->syncPermissions($collectionManagerPermissions);

        $collectionViewerRole = Role::query()->firstOrCreate([
            'name' => 'collection-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Collection Viewer',
            'is_active' => true,
        ]);
        $collectionViewerRole->syncPermissions($collectionViewerPermissions);

        $settlementManagerRole = Role::query()->firstOrCreate([
            'name' => 'settlement-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Settlement Manager',
            'is_active' => true,
        ]);
        $settlementManagerRole->syncPermissions($settlementManagerPermissions);

        $settlementViewerRole = Role::query()->firstOrCreate([
            'name' => 'settlement-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Settlement Viewer',
            'is_active' => true,
        ]);
        $settlementViewerRole->syncPermissions($settlementViewerPermissions);

        $activityLogViewerRole = Role::query()->firstOrCreate([
            'name' => 'activity-log-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Activity Log Viewer',
            'is_active' => true,
        ]);
        $activityLogViewerRole->syncPermissions($activityLogPermissions);

        $primaryAdmin = User::query()->find(1);
        if ($primaryAdmin) {
            $primaryAdmin->assignRole($superAdminRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
