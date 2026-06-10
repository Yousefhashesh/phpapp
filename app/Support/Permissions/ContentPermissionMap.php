<?php

namespace App\Support\Permissions;

class ContentPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'content.page', 'group' => 'content', 'label' => 'دخول صفحة المحتوى (نصوص الشحن)', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'content.view', 'group' => 'content', 'label' => 'عرض سطور المحتوى', 'type' => 'action'],
        ['name' => 'content.create', 'group' => 'content', 'label' => 'إضافة محتوى جديد', 'type' => 'button'],
        ['name' => 'content.update', 'group' => 'content', 'label' => 'تعديل محتوى', 'type' => 'button'],
        ['name' => 'content.delete', 'group' => 'content', 'label' => 'حذف محتوى', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'name' => 'الاسم',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const CONTENT_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'content.column.name.view',
        'created_at' => 'content.column.created_at.view',
        'updated_at' => 'content.column.updated_at.view',
    ];

    public const CONTENT_EDIT_COLUMNS = [
        'name' => 'content.column.name.edit',
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

        foreach (self::CONTENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'content',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للمحتوى',
                'type' => 'column',
            ];
        }

        foreach (self::CONTENT_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'content',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للمحتوى',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
