<?php

namespace App\Support\Permissions;

class ActivityLogPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'activity-log.page', 'group' => 'activity-logs', 'label' => 'دخول صفحة سجل العمليات (اللوجز)', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'activity-log.view', 'group' => 'activity-logs', 'label' => 'عرض سجل العمليات', 'type' => 'action'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'user_id' => 'رقم المستخدم',
        'user' => 'المستخدم',
        'login_session' => 'جلسة تسجيل الدخول',
        'activity' => 'النشاط',
        'description' => 'الوصف/بينات التعديل',
        'type' => 'النوع',
        'old_values' => 'القيم السابقة',
        'new_values' => 'القيم الجديدة',
        'created_at' => 'تاريخ الحركة',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'activity-log.column.user_id.view',
        'user' => 'activity-log.column.user_id.view',
        'login_session' => 'activity-log.column.user_id.view',
        'activity' => 'activity-log.column.activity.view',
        'description' => 'activity-log.column.description.view',
        'type' => 'activity-log.column.type.view',
        'old_values' => 'activity-log.column.old_values.view',
        'new_values' => 'activity-log.column.new_values.view',
        'created_at' => 'activity-log.column.created_at.view',
        'updated_at' => 'activity-log.column.updated_at.view',
    ];

    public const EDIT_COLUMNS = [];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];

        foreach (self::VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'activity-logs',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في سجل العمليات',
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'activity-logs',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في سجل العمليات',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
