<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AclMatrixController extends Controller
{
    public function matrix(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'pages' => [
                ...$this->permissionState($user, array_column(ExpensePermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AccountPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(ContentPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(SettingPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AreaPlanPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(OperationsPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(RefusedReasonPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(OrdersPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(ActivityLogPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(DashboardPermissionMap::PAGE_PERMISSIONS, 'name')),
            ],
            'actions' => [
                ...$this->permissionState($user, array_column(ExpensePermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AccountPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(ContentPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(AreaPlanPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(OperationsPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(RefusedReasonPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(OrdersPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(ActivityLogPermissionMap::ACTION_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_column(DashboardPermissionMap::PAGE_PERMISSIONS, 'name')),
                ...$this->permissionState($user, array_values(DashboardPermissionMap::CARD_VIEW_PERMISSIONS)),
                ...$this->permissionState($user, array_values(DashboardPermissionMap::CHART_VIEW_PERMISSIONS)),
                ...$this->permissionState($user, array_column(SettingPermissionMap::ACTION_PERMISSIONS, 'name')),
            ],
            'setting_pages' => $this->permissionState($user, array_column(SettingPermissionMap::PAGE_PERMISSIONS, 'name')),
            'expense_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ExpensePermissionMap::EXPENSE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ExpensePermissionMap::EXPENSE_EDIT_COLUMNS)),
            ],
            'expense_category_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ExpensePermissionMap::EXPENSE_CATEGORY_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ExpensePermissionMap::EXPENSE_CATEGORY_EDIT_COLUMNS)),
            ],
            'user_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::USER_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AccountPermissionMap::USER_EDIT_COLUMNS)),
            ],
            'profile_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::PROFILE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AccountPermissionMap::PROFILE_EDIT_COLUMNS)),
            ],
            'client_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::CLIENT_VIEW_COLUMNS))),
            ],
            'shipper_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AccountPermissionMap::SHIPPER_VIEW_COLUMNS))),
            ],
            'content_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(ContentPermissionMap::CONTENT_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(ContentPermissionMap::CONTENT_EDIT_COLUMNS)),
            ],
            'governorate_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::GOVERNORATE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::GOVERNORATE_EDIT_COLUMNS)),
            ],
            'city_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::CITY_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::CITY_EDIT_COLUMNS)),
            ],
            'plan_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::PLAN_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::PLAN_EDIT_COLUMNS)),
            ],
            'plan_price_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(AreaPlanPermissionMap::PLAN_PRICE_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(AreaPlanPermissionMap::PLAN_PRICE_EDIT_COLUMNS)),
            ],
            'material_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OperationsPermissionMap::MATERIAL_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OperationsPermissionMap::MATERIAL_EDIT_COLUMNS)),
            ],
            'material_request_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OperationsPermissionMap::MATERIAL_REQUEST_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OperationsPermissionMap::MATERIAL_REQUEST_EDIT_COLUMNS)),
            ],
            'material_request_item_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OperationsPermissionMap::MATERIAL_REQUEST_ITEM_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OperationsPermissionMap::MATERIAL_REQUEST_ITEM_EDIT_COLUMNS)),
            ],
            'pickup_request_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OperationsPermissionMap::PICKUP_REQUEST_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OperationsPermissionMap::PICKUP_REQUEST_EDIT_COLUMNS)),
            ],
            'visit_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OperationsPermissionMap::VISIT_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OperationsPermissionMap::VISIT_EDIT_COLUMNS)),
            ],
            'refused_reason_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(RefusedReasonPermissionMap::VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(RefusedReasonPermissionMap::EDIT_COLUMNS)),
            ],
            'order_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(OrdersPermissionMap::VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(OrdersPermissionMap::EDIT_COLUMNS)),
            ],
            'shipper_collection_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(CollectionsReturnsSettlementsPermissionMap::SHIPPER_COLLECTION_EDIT_COLUMNS)),
            ],
            'shipper_return_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_EDIT_COLUMNS)),
            ],
            'client_settlement_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_EDIT_COLUMNS)),
            ],
            'client_return_columns' => [
                'view' => $this->permissionState($user, array_values(array_filter(CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_VIEW_COLUMNS))),
                'edit' => $this->permissionState($user, array_values(CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_EDIT_COLUMNS)),
            ],
            'activity_log_columns' => [
                'view' => $this->permissionState($user, array_filter(ActivityLogPermissionMap::VIEW_COLUMNS)),
            ],
            'ability_rules' => $this->getFlatPermissionList($user),
        ]);
    }

    private function getFlatPermissionList(\App\Models\User $user): array
    {
        if ($user->id === 1 || $user->hasRole('super-admin')) {
            $all = \App\Models\Permission::pluck('name')->toArray();
            
            // Add a wildcard for CASL or frontend logic that might need it
            if (!in_array('*', $all)) {
                $all[] = '*';
            }
            
            return $all;
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, bool>
     */
    private function permissionState(object $user, array $permissions): array
    {
        $result = [];
        $isSuperAdmin = $user->id === 1 || ($user instanceof \App\Models\User && $user->hasRole('super-admin'));

        foreach ($permissions as $permission) {
            $result[$permission] = $isSuperAdmin ? true : $user->can($permission);
        }

        return $result;
    }
}
