<?php

namespace App\Support\Permissions;

class AccountPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'user.page', 'group' => 'users', 'label' => 'دخول صفحة المستخدمين', 'type' => 'page'],
        ['name' => 'client.page', 'group' => 'clients', 'label' => 'دخول صفحة العملاء', 'type' => 'page'],
        ['name' => 'shipper.page', 'group' => 'shippers', 'label' => 'دخول صفحة المناديب', 'type' => 'page'],
        ['name' => 'user.profile.page', 'group' => 'users', 'label' => 'دخول صفحة الملف الشخصي', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'user.view', 'group' => 'users', 'label' => 'عرض المستخدمين', 'type' => 'action'],
        ['name' => 'user.create', 'group' => 'users', 'label' => 'إضافة مستخدم جديد', 'type' => 'button'],
        ['name' => 'user.update', 'group' => 'users', 'label' => 'تعديل مستخدم', 'type' => 'button'],
        ['name' => 'user.delete', 'group' => 'users', 'label' => 'حذف مستخدم', 'type' => 'button'],
        ['name' => 'user.button.type.user', 'group' => 'users', 'label' => 'تحويل الحساب إلى مستخدم نظام', 'type' => 'button'],
        ['name' => 'user.button.type.client', 'group' => 'users', 'label' => 'تحويل الحساب إلى عميل', 'type' => 'button'],
        ['name' => 'user.button.type.shipper', 'group' => 'users', 'label' => 'تحويل الحساب إلى مندوب', 'type' => 'button'],

        ['name' => 'client.view', 'group' => 'clients', 'label' => 'عرض العملاء', 'type' => 'action'],
        ['name' => 'shipper.view', 'group' => 'shippers', 'label' => 'عرض المناديب', 'type' => 'action'],
        ['name' => 'user.profile.view', 'group' => 'users', 'label' => 'عرض ملفي الشخصي', 'type' => 'action'],
        ['name' => 'user.profile.update', 'group' => 'users', 'label' => 'تعديل ملفي الشخصي', 'type' => 'button'],

        ['name' => 'role.view', 'group' => 'roles', 'label' => 'عرض الأدوار (الصلاحيات)', 'type' => 'action'],
        ['name' => 'role.create', 'group' => 'roles', 'label' => 'إضافة دور جديد', 'type' => 'button'],
        ['name' => 'role.update', 'group' => 'roles', 'label' => 'تعديل دور', 'type' => 'button'],
        ['name' => 'role.delete', 'group' => 'roles', 'label' => 'حذف دور', 'type' => 'button'],
        ['name' => 'user.update-role', 'group' => 'users', 'label' => 'تعديل صلاحيات المستخدم (الأدوار)', 'type' => 'button'],
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'phone' => 'الهاتف',
        'avatar' => 'الصورة الشخصية',
        'roles' => 'الأدوار (الصلاحيات)',
        'account_type' => 'نوع الحساب',
        'shipper' => 'بيانات المندوب',
        'client' => 'بيانات العميل',
        'is_blocked' => 'محظور',
        'login_sessions' => 'جلسات تسجيل الدخول',
        'password' => 'كلمة المرور',
        'commission_rate' => 'نسبة العمولة',
        'address' => 'العنوان',
        'plan_id' => 'خطة الأسعار',
        'plan' => 'خطة الأسعار',
        'shipping_content_id' => 'محتوى الشحنة',
        'shippingContent' => 'محتوى الشحنة',
        'can_settle_before_shipper_collected' => 'تسوية قبل التحصيل من المندوب',
        'user_id' => 'رقم المستخدم',
        'user' => 'المستخدم',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public const PROFILE_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'user.profile.column.name.view',
        'username' => 'user.profile.column.username.view',
        'phone' => 'user.profile.column.phone.view',
        'avatar' => 'user.profile.column.avatar.view',
        'roles' => 'user.profile.column.roles.view',
        'account_type' => null,
        'shipper' => 'user.profile.column.shipper.view',
        'client' => 'user.profile.column.client.view',
        'created_at' => 'user.profile.column.created_at.view',
        'updated_at' => 'user.profile.column.updated_at.view',
    ];

    public const PROFILE_EDIT_COLUMNS = [
        'name' => 'user.profile.column.name.edit',
        'username' => 'user.profile.column.username.edit',
        'phone' => 'user.profile.column.phone.edit',
        'avatar' => 'user.profile.column.avatar.edit',
        'password' => 'user.profile.column.password.edit',
    ];

    public const USER_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'user.column.name.view',
        'username' => 'user.column.username.view',
        'phone' => 'user.column.phone.view',
        'avatar' => 'user.column.avatar.view',
        'is_blocked' => 'user.column.is_blocked.view',
        'account_type' => null,
        'roles' => 'user.column.roles.view',
        'shipper' => 'user.column.shipper.view',
        'client' => 'user.column.client.view',
        'login_sessions' => 'user.column.login_sessions.view',
        'created_at' => 'user.column.created_at.view',
        'updated_at' => 'user.column.updated_at.view',
    ];

    public const USER_EDIT_COLUMNS = [
        'name' => 'user.column.name.edit',
        'username' => 'user.column.username.edit',
        'phone' => 'user.column.phone.edit',
        'avatar' => 'user.column.avatar.edit',
        'password' => 'user.column.password.edit',
        'is_blocked' => 'user.column.is_blocked.edit',
        'account_type' => 'user.column.account_type.edit',
        'commission_rate' => 'user.column.commission_rate.edit',
        'address' => 'user.column.address.edit',
        'plan_id' => 'user.column.plan_id.edit',
        'shipping_content_id' => 'user.column.shipping_content_id.edit',
        'can_settle_before_shipper_collected' => 'user.column.can_settle_before_shipper_collected.edit',
    ];

    public const CLIENT_VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'client.column.user_id.view',
        'address' => 'client.column.address.view',
        'plan_id' => 'client.column.plan_id.view',
        'shipping_content_id' => 'client.column.shipping_content_id.view',
        'can_settle_before_shipper_collected' => 'client.column.can_settle_before_shipper_collected.view',
        'user' => 'client.column.user.view',
        'plan' => 'client.column.plan.view',
        'shippingContent' => 'client.column.shipping_content.view',
        'created_at' => 'client.column.created_at.view',
        'updated_at' => 'client.column.updated_at.view',
    ];

    public const SHIPPER_VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'shipper.column.user_id.view',
        'commission_rate' => 'shipper.column.commission_rate.view',
        'user' => 'shipper.column.user.view',
        'created_at' => 'shipper.column.created_at.view',
        'updated_at' => 'shipper.column.updated_at.view',
    ];

    public const ACCOUNT_TYPE_BUTTON_PERMISSIONS = [
        0 => 'user.button.type.user',
        1 => 'user.button.type.client',
        2 => 'user.button.type.shipper',
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

        foreach (self::USER_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للمستخدم',
                'type' => 'column',
            ];
        }

        foreach (self::USER_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للمستخدم',
                'type' => 'column',
            ];
        }

        foreach (self::PROFILE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في الملف الشخصي',
                'type' => 'column',
            ];
        }

        foreach (self::PROFILE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في الملف الشخصي',
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'clients',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للعميل',
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'shippers',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' للمندوب',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
