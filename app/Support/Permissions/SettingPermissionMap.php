<?php

namespace App\Support\Permissions;

class SettingPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'setting.page', 'group' => 'setting', 'label' => 'دخول صفحة الإعدادات', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'setting.bypass-working-hours', 'group' => 'setting', 'label' => 'تجاوز قيود ساعات العمل', 'type' => 'button'],
    ];

    /**
     * @return array<int, array{name:string,group:string,label:string,type:string}>
     */
    public static function allPermissionDefinitions(): array
    {
        return [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];
    }
}
