<?php

namespace App\Support\Permissions;

class SettingPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'setting.page', 'group' => 'setting', 'label' => 'Settings page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'setting.bypass-working-hours', 'group' => 'setting', 'label' => 'Bypass working hours', 'type' => 'button'],
        ['name' => 'setting.financial-formulas.update', 'group' => 'setting', 'label' => 'Update financial formulas', 'type' => 'button'],
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
