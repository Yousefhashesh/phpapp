<?php

namespace App\Support\Permissions;

class AreaPlanPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'area.page', 'group' => 'areas', 'label' => 'دخول صفحة المناطق (المحافظات والمدن)', 'type' => 'page'],
        ['name' => 'plan.page', 'group' => 'plans', 'label' => 'دخول صفحة خطط الأسعار', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'area.view', 'group' => 'areas', 'label' => 'عرض المناطق', 'type' => 'action'],
        ['name' => 'area.create', 'group' => 'areas', 'label' => 'إضافة منطقة جديدة', 'type' => 'button'],
        ['name' => 'area.update', 'group' => 'areas', 'label' => 'تعديل منطقة', 'type' => 'button'],
        ['name' => 'area.delete', 'group' => 'areas', 'label' => 'حذف منطقة', 'type' => 'button'],

        ['name' => 'plan.view', 'group' => 'plans', 'label' => 'عرض خطط الأسعار', 'type' => 'action'],
        ['name' => 'plan.create', 'group' => 'plans', 'label' => 'إضافة خطة أسعار جديدة', 'type' => 'button'],
        ['name' => 'plan.update', 'group' => 'plans', 'label' => 'تعديل خطة أسعار', 'type' => 'button'],
        ['name' => 'plan.delete', 'group' => 'plans', 'label' => 'حذف خطة أسعار', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'name' => 'الاسم',
        'follow_up_hours' => 'ساعات المتابعة',
        'default_shipper_user_id' => 'المندوب الافتراضي',
        'defaultShipper' => 'المندوب الافتراضي',
        'cities' => 'المدن',
        'governorate_id' => 'رقم المحافظة',
        'governorate' => 'المحافظة',
        'order_count' => 'عدد الأوردات',
        'prices' => 'الأسعار',
        'plan_id' => 'رقم الخطة',
        'price' => 'السعر',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const GOVERNORATE_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'area.column.name.view',
        'follow_up_hours' => 'area.column.follow_up_hours.view',
        'default_shipper_user_id' => 'area.column.default_shipper_user_id.view',
        'defaultShipper' => 'area.column.default_shipper.view',
        'cities' => 'area.column.cities.view',
        'created_at' => 'area.column.created_at.view',
        'updated_at' => 'area.column.updated_at.view',
    ];

    public const GOVERNORATE_EDIT_COLUMNS = [
        'name' => 'area.column.name.edit',
        'follow_up_hours' => 'area.column.follow_up_hours.edit',
        'default_shipper_user_id' => 'area.column.default_shipper_user_id.edit',
        'cities' => 'area.column.cities.edit',
    ];

    public const CITY_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'area.column.city_name.view',
        'governorate_id' => 'area.column.area_id.view',
        'created_at' => 'area.column.city_created_at.view',
        'updated_at' => 'area.column.city_updated_at.view',
    ];

    public const CITY_EDIT_COLUMNS = [
        'name' => 'area.column.city_name.edit',
    ];

    public const PLAN_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'plan.column.name.view',
        'order_count' => 'plan.column.order_count.view',
        'prices' => 'plan.column.prices.view',
        'created_at' => 'plan.column.created_at.view',
        'updated_at' => 'plan.column.updated_at.view',
    ];

    public const PLAN_EDIT_COLUMNS = [
        'name' => 'plan.column.name.edit',
        'order_count' => 'plan.column.order_count.edit',
        'prices' => 'plan.column.prices.edit',
    ];

    public const PLAN_PRICE_VIEW_COLUMNS = [
        'id' => null,
        'plan_id' => 'plan-price.column.plan_id.view',
        'governorate_id' => 'plan-price.column.area_id.view',
        'price' => 'plan-price.column.price.view',
        'governorate' => 'plan-price.column.area.view',
        'created_at' => 'plan-price.column.created_at.view',
        'updated_at' => 'plan-price.column.updated_at.view',
    ];

    public const PLAN_PRICE_EDIT_COLUMNS = [
        'governorate_id' => 'plan-price.column.area_id.edit',
        'price' => 'plan-price.column.price.edit',
    ];

    /**
     * @return array<int, array{name:string,group:string,label:string,type:string}>
     */
    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];

        foreach (self::GOVERNORATE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'areas',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المحافظة',
                'type' => 'column',
            ];
        }

        foreach (self::GOVERNORATE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'areas',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المحافظة',
                'type' => 'column',
            ];
        }

        foreach (self::CITY_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'areas',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المدينة',
                'type' => 'column',
            ];
        }

        foreach (self::CITY_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'areas',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في المدينة',
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'plans',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في الخطة',
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'plans',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في الخطة',
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_PRICE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'plan-prices',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في سعر الخطة',
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_PRICE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'plan-prices',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في سعر الخطة',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
