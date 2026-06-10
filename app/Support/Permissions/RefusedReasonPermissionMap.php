<?php

namespace App\Support\Permissions;

class RefusedReasonPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'refused-reason.page', 'group' => 'refused-reasons', 'label' => 'دخول صفحة أسباب الرفض/الحالات', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'refused-reason.view', 'group' => 'refused-reasons', 'label' => 'عرض أسباب الرفض', 'type' => 'action'],
        ['name' => 'refused-reason.create', 'group' => 'refused-reasons', 'label' => 'إضافة سبب جديد', 'type' => 'button'],
        ['name' => 'refused-reason.update', 'group' => 'refused-reasons', 'label' => 'تعديل سبب', 'type' => 'button'],
        ['name' => 'refused-reason.delete', 'group' => 'refused-reasons', 'label' => 'حذف سبب', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'reason' => 'السبب',
        'status' => 'الحالة المرتبطة',
        'is_active' => 'نشط',
        'is_clear' => 'تصفير المبالغ',
        'is_edit_amount' => 'يسمح بتعديل المبلغ',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'reason' => 'refused-reason.column.reason.view',
        'status' => 'refused-reason.column.status.view',
        'is_active' => 'refused-reason.column.is_active.view',
        'is_clear' => 'refused-reason.column.is_clear.view',
        'is_edit_amount' => 'refused-reason.column.is_edit_amount.view',
        'created_at' => 'refused-reason.column.created_at.view',
        'updated_at' => 'refused-reason.column.updated_at.view',
    ];

    public const EDIT_COLUMNS = [
        'reason' => 'refused-reason.column.reason.edit',
        'status' => 'refused-reason.column.status.edit',
        'is_active' => 'refused-reason.column.is_active.edit',
        'is_clear' => 'refused-reason.column.is_clear.edit',
        'is_edit_amount' => 'refused-reason.column.is_edit_amount.edit',
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

        foreach (self::VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'refused-reasons',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في أسباب الرفض',
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'refused-reasons',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في أسباب الرفض',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
