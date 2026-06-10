<?php

namespace App\Support\Permissions;

class CollectionsReturnsSettlementsPermissionMap
{
    // =========== Shipper Collections ===========
    public const SHIPPER_COLLECTION_PAGE_PERMISSIONS = [
        ['name' => 'shipper-collection.page', 'group' => 'shipper-collections', 'label' => 'دخول صفحة تحصيل المناديب', 'type' => 'page'],
    ];

    public const SHIPPER_COLLECTION_ACTION_PERMISSIONS = [
        ['name' => 'shipper-collection.view', 'group' => 'shipper-collections', 'label' => 'عرض تحصيلات المناديب', 'type' => 'action'],
        ['name' => 'shipper-collection.create', 'group' => 'shipper-collections', 'label' => 'إنشاء تحصيل مندوب جديد', 'type' => 'button'],
        ['name' => 'shipper-collection.update', 'group' => 'shipper-collections', 'label' => 'تعديل تحصيل مندوب', 'type' => 'button'],
        ['name' => 'shipper-collection.delete', 'group' => 'shipper-collections', 'label' => 'حذف تحصيل مندوب', 'type' => 'button'],
        ['name' => 'shipper-collection.unlock', 'group' => 'shipper-collections', 'label' => 'إلغاء قفل التحصيل', 'type' => 'button'],
        ['name' => 'shipper-collection.approve', 'group' => 'shipper-collections', 'label' => 'الموافقة على التحصيل', 'type' => 'button'],
        ['name' => 'shipper-collection.reject', 'group' => 'shipper-collections', 'label' => 'رفض التحصيل', 'type' => 'button'],
        ['name' => 'shipper-collection.export', 'group' => 'shipper-collections', 'label' => 'تصدير التحصيلات (إكسيل)', 'type' => 'button'],
    ];

    public const SHIPPER_COLLECTION_VIEW_COLUMNS = [
        'id' => null,
        'shipper_user_id' => 'shipper-collection.column.shipper_user_id.view',
        'collection_date' => 'shipper-collection.column.collection_date.view',
        'total_amount' => 'shipper-collection.column.total_amount.view',
        'number_of_orders' => 'shipper-collection.column.number_of_orders.view',
        'fees' => null,
        'shipper_fees' => 'shipper-collection.column.shipper_fees.view',
        'net_amount' => 'shipper-collection.column.net_amount.view',
        'status' => 'shipper-collection.column.status.view',
        'approval_status' => null,
        'shipper' => 'shipper-collection.column.shipper_user_id.view',
        'created_at' => 'shipper-collection.column.created_at.view',
        'updated_at' => 'shipper-collection.column.updated_at.view',
    ];

    public const SHIPPER_COLLECTION_EDIT_COLUMNS = [
        'shipper_user_id' => 'shipper-collection.column.shipper_user_id.edit',
        'collection_date' => 'shipper-collection.column.collection_date.edit',
        'shipper_fees' => 'shipper-collection.column.shipper_fees.edit',
        'status' => 'shipper-collection.column.status.edit',
    ];

    // =========== Shipper Returns ===========
    public const SHIPPER_RETURN_PAGE_PERMISSIONS = [
        ['name' => 'shipper-return.page', 'group' => 'shipper-returns', 'label' => 'دخول صفحة مرتجعات المناديب', 'type' => 'page'],
    ];

    public const SHIPPER_RETURN_ACTION_PERMISSIONS = [
        ['name' => 'shipper-return.view', 'group' => 'shipper-returns', 'label' => 'عرض مرتجعات المناديب', 'type' => 'action'],
        ['name' => 'shipper-return.create', 'group' => 'shipper-returns', 'label' => 'إنشاء مرتجع مندوب جديد', 'type' => 'button'],
        ['name' => 'shipper-return.update', 'group' => 'shipper-returns', 'label' => 'تعديل مرتجع مندوب', 'type' => 'button'],
        ['name' => 'shipper-return.delete', 'group' => 'shipper-returns', 'label' => 'حذف مرتجع مندوب', 'type' => 'button'],
        ['name' => 'shipper-return.unlock', 'group' => 'shipper-returns', 'label' => 'إلغاء قفل المرتجع', 'type' => 'button'],
        ['name' => 'shipper-return.approve', 'group' => 'shipper-returns', 'label' => 'الموافقة على المرتجع', 'type' => 'button'],
        ['name' => 'shipper-return.reject', 'group' => 'shipper-returns', 'label' => 'رفض المرتجع', 'type' => 'button'],
        ['name' => 'shipper-return.export', 'group' => 'shipper-returns', 'label' => 'تصدير المرتجعات (إكسيل)', 'type' => 'button'],
    ];

    public const SHIPPER_RETURN_VIEW_COLUMNS = [
        'id' => null,
        'shipper_user_id' => 'shipper-return.column.shipper_user_id.view',
        'return_date' => 'shipper-return.column.return_date.view',
        'number_of_orders' => 'shipper-return.column.number_of_orders.view',
        'notes' => 'shipper-return.column.notes.view',
        'client_tracking_number' => null,
        'internal_tracking_number' => null,
        'status' => 'shipper-return.column.status.view',
        'approval_status' => null,
        'shipper' => 'shipper-return.column.shipper_user_id.view',
        'created_at' => 'shipper-return.column.created_at.view',
        'updated_at' => 'shipper-return.column.updated_at.view',
    ];

    public const SHIPPER_RETURN_EDIT_COLUMNS = [
        'shipper_user_id' => 'shipper-return.column.shipper_user_id.edit',
        'return_date' => 'shipper-return.column.return_date.edit',
        'notes' => 'shipper-return.column.notes.edit',
        'client_tracking_number' => null,
        'internal_tracking_number' => null,
        'status' => 'shipper-return.column.status.edit',
    ];

    // =========== Client Settlements ===========
    public const CLIENT_SETTLEMENT_PAGE_PERMISSIONS = [
        ['name' => 'client-settlement.page', 'group' => 'client-settlements', 'label' => 'دخول صفحة تسويات العملاء', 'type' => 'page'],
    ];

    public const CLIENT_SETTLEMENT_ACTION_PERMISSIONS = [
        ['name' => 'client-settlement.view', 'group' => 'client-settlements', 'label' => 'عرض تسويات العملاء', 'type' => 'action'],
        ['name' => 'client-settlement.create', 'group' => 'client-settlements', 'label' => 'إنشاء تسوية عميل جديدة', 'type' => 'button'],
        ['name' => 'client-settlement.update', 'group' => 'client-settlements', 'label' => 'تعديل تسوية عميل', 'type' => 'button'],
        ['name' => 'client-settlement.delete', 'group' => 'client-settlements', 'label' => 'حذف تسوية عميل', 'type' => 'button'],
        ['name' => 'client-settlement.unlock', 'group' => 'client-settlements', 'label' => 'إلغاء قفل التسوية', 'type' => 'button'],
        ['name' => 'client-settlement.approve', 'group' => 'client-settlements', 'label' => 'الموافقة على التسوية', 'type' => 'button'],
        ['name' => 'client-settlement.reject', 'group' => 'client-settlements', 'label' => 'رفض التسوية', 'type' => 'button'],
        ['name' => 'client-settlement.export', 'group' => 'client-settlements', 'label' => 'تصدير التسويات (إكسيل)', 'type' => 'button'],
    ];

    public const CLIENT_SETTLEMENT_VIEW_COLUMNS = [
        'id' => null,
        'client_user_id' => 'client-settlement.column.client_user_id.view',
        'settlement_date' => 'client-settlement.column.settlement_date.view',
        'total_amount' => 'client-settlement.column.total_amount.view',
        'number_of_orders' => 'client-settlement.column.number_of_orders.view',
        'fees' => 'client-settlement.column.fees.view',
        'net_amount' => 'client-settlement.column.net_amount.view',
        'status' => 'client-settlement.column.status.view',
        'approval_status' => null,
        'client' => 'client-settlement.column.client_user_id.view',
        'created_at' => 'client-settlement.column.created_at.view',
        'updated_at' => 'client-settlement.column.updated_at.view',
    ];

    public const CLIENT_SETTLEMENT_EDIT_COLUMNS = [
        'client_user_id' => 'client-settlement.column.client_user_id.edit',
        'settlement_date' => 'client-settlement.column.settlement_date.edit',
        'fees' => 'client-settlement.column.fees.edit',
        'status' => 'client-settlement.column.status.edit',
    ];

    // =========== Client Returns ===========
    public const CLIENT_RETURN_PAGE_PERMISSIONS = [
        ['name' => 'client-return.page', 'group' => 'client-returns', 'label' => 'دخول صفحة مرتجعات العملاء', 'type' => 'page'],
    ];

    public const CLIENT_RETURN_ACTION_PERMISSIONS = [
        ['name' => 'client-return.view', 'group' => 'client-returns', 'label' => 'عرض مرتجعات العملاء', 'type' => 'action'],
        ['name' => 'client-return.create', 'group' => 'client-returns', 'label' => 'إنشاء مرتجع عميل جديد', 'type' => 'button'],
        ['name' => 'client-return.update', 'group' => 'client-returns', 'label' => 'تعديل مرتجع عميل', 'type' => 'button'],
        ['name' => 'client-return.delete', 'group' => 'client-returns', 'label' => 'حذف مرتجع عميل', 'type' => 'button'],
        ['name' => 'client-return.unlock', 'group' => 'client-returns', 'label' => 'إلغاء قفل المرتجع', 'type' => 'button'],
        ['name' => 'client-return.approve', 'group' => 'client-returns', 'label' => 'الموافقة على المرتجع', 'type' => 'button'],
        ['name' => 'client-return.reject', 'group' => 'client-returns', 'label' => 'رفض المرتجع', 'type' => 'button'],
        ['name' => 'client-return.export', 'group' => 'client-returns', 'label' => 'تصدير المرتجعات (إكسيل)', 'type' => 'button'],
    ];

    public const CLIENT_RETURN_VIEW_COLUMNS = [
        'id' => null,
        'client_user_id' => 'client-return.column.client_user_id.view',
        'return_date' => 'client-return.column.return_date.view',
        'number_of_orders' => 'client-return.column.number_of_orders.view',
        'notes' => 'client-return.column.notes.view',
        'status' => 'client-return.column.status.view',
        'approval_status' => null,
        'client' => 'client-return.column.client_user_id.view',
        'created_at' => 'client-return.column.created_at.view',
        'updated_at' => 'client-return.column.updated_at.view',
    ];

    public const CLIENT_RETURN_EDIT_COLUMNS = [
        'client_user_id' => 'client-return.column.client_user_id.edit',
        'return_date' => 'client-return.column.return_date.edit',
        'notes' => 'client-return.column.notes.edit',
        'status' => 'client-return.column.status.edit',
    ];

    private const COLUMN_NAMES_AR = [
        'id' => 'المعرف',
        'shipper_user_id' => 'المندوب',
        'client_user_id' => 'العميل',
        'collection_date' => 'تاريخ التحصيل',
        'return_date' => 'تاريخ المرتجع',
        'settlement_date' => 'تاريخ التسوية',
        'total_amount' => 'إجمالي المبلغ',
        'number_of_orders' => 'عدد الأوردات',
        'fees' => 'رسوم إضافية',
        'shipper_fees' => 'عمولة المندوب',
        'net_amount' => 'صافي المبلغ',
        'status' => 'الحالة',
        'notes' => 'ملاحظات',
        'shipper' => 'المندوب',
        'client' => 'العميل',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'تاريخ التحديث',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [];

        // Shipper Collections
        $permissions = array_merge($permissions, self::SHIPPER_COLLECTION_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::SHIPPER_COLLECTION_ACTION_PERMISSIONS);

        foreach (self::SHIPPER_COLLECTION_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-collections',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تحصيل المناديب',
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_COLLECTION_EDIT_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-collections',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تحصيل المناديب',
                'type' => 'column',
            ];
        }

        // Shipper Returns
        $permissions = array_merge($permissions, self::SHIPPER_RETURN_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::SHIPPER_RETURN_ACTION_PERMISSIONS);

        foreach (self::SHIPPER_RETURN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-returns',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في مرتجع المناديب',
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_RETURN_EDIT_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-returns',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في مرتجع المناديب',
                'type' => 'column',
            ];
        }

        // Client Settlements
        $permissions = array_merge($permissions, self::CLIENT_SETTLEMENT_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::CLIENT_SETTLEMENT_ACTION_PERMISSIONS);

        foreach (self::CLIENT_SETTLEMENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-settlements',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تسوية العملاء',
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_SETTLEMENT_EDIT_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'client-settlements',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في تسوية العملاء',
                'type' => 'column',
            ];
        }

        // Client Returns
        $permissions = array_merge($permissions, self::CLIENT_RETURN_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::CLIENT_RETURN_ACTION_PERMISSIONS);

        foreach (self::CLIENT_RETURN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-returns',
                'label' => 'عرض عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في مرتجع العملاء',
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_RETURN_EDIT_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'client-returns',
                'label' => 'تعديل عمود ' . (self::COLUMN_NAMES_AR[$column] ?? $column) . ' في مرتجع العملاء',
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
